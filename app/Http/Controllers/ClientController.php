<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Models\Setting;
use App\Services\AuditLogService;
use App\Services\ClientService;
use App\Traits\Auditable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    use Auditable;

    protected string $model = 'clients';

    public function index(Request $request)
    {
        // ── Eager-loaded query with aggregated sales data ────────
        $clients = Client::query()
            ->search($request->input('search'))
            ->when($request->input('status'), fn($q, $s) => $q->where('status', $s))
            ->when($request->input('type'), fn($q, $t) => $q->where('type', $t))
            ->withCount(['salesOrders as sales_to_date' => function ($q) {
                $q->select(DB::raw('COALESCE(SUM(total), 0)'))
                  ->whereNotIn('status', ['cancelled', 'draft']);
            }])
            ->withCount(['invoices as current_balance' => function ($q) {
                $q->select(DB::raw('COALESCE(SUM(total_amount - paid_amount), 0)'))
                  ->whereIn('status', ['unpaid', 'partial']);
            }])
            ->withMax('invoices as last_invoice_date', 'invoice_date')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // ── Summary cards ────────────────────────────────────────
        $totalActiveClients = Client::active()->count();

        $totalReceivables = (string) Invoice::whereIn('status', ['unpaid', 'partial'])
            ->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0) as total')
            ->value('total');

        // Monthly sales growth: compare current month vs previous month
        $currentMonthSales = (float) SalesOrder::whereNotIn('status', ['cancelled', 'draft'])
            ->whereYear('order_date', now()->year)
            ->whereMonth('order_date', now()->month)
            ->sum('total');

        $prevMonthSales = (float) SalesOrder::whereNotIn('status', ['cancelled', 'draft'])
            ->whereYear('order_date', now()->subMonth()->year)
            ->whereMonth('order_date', now()->subMonth()->month)
            ->sum('total');

        $monthlySalesGrowth = $prevMonthSales > 0
            ? round((($currentMonthSales - $prevMonthSales) / $prevMonthSales) * 100, 1)
            : ($currentMonthSales > 0 ? 100.0 : 0.0);

        // Top spender: client with highest total confirmed+ sales
        $topSpender = Client::query()
            ->withCount(['salesOrders as lifetime_sales' => function ($q) {
                $q->select(DB::raw('COALESCE(SUM(total), 0)'))
                  ->whereNotIn('status', ['cancelled', 'draft']);
            }])
            ->orderByDesc('lifetime_sales')
            ->first();

        $summary = compact(
            'totalActiveClients',
            'totalReceivables',
            'monthlySalesGrowth',
            'currentMonthSales',
            'topSpender',
        );

        // ── Pipeline data (grouped by type) ─────────────────────
        $pipelineClients = Client::query()
            ->where('status', 'active')
            ->withCount(['salesOrders as sales_to_date' => function ($q) {
                $q->select(DB::raw('COALESCE(SUM(total), 0)'))
                  ->whereNotIn('status', ['cancelled', 'draft']);
            }])
            ->withCount(['invoices as current_balance' => function ($q) {
                $q->select(DB::raw('COALESCE(SUM(total_amount - paid_amount), 0)'))
                  ->whereIn('status', ['unpaid', 'partial']);
            }])
            ->latest()
            ->get()
            ->groupBy('type');

        // ── Conversion rate: leads → customer this month ────────
        $monthStart = now()->startOfMonth();
        $leadsStartOfMonth = Client::where('type', 'lead')
            ->where('created_at', '<', $monthStart)
            ->count()
            + Client::where('created_at', '>=', $monthStart)
                ->whereIn('type', ['lead', 'prospect', 'customer'])
                ->count();

        $convertedThisMonth = (int) DB::table('activity_logs')
            ->where('module', 'clients')
            ->where('action', 'update')
            ->where('created_at', '>=', $monthStart)
            ->whereRaw("json_extract(changes, '$.type.new') = 'customer'")
            ->count();

        $conversionRate = $leadsStartOfMonth > 0
            ? round(($convertedThisMonth / $leadsStartOfMonth) * 100, 1)
            : 0.0;

        // ── Lead follow-up grace period for stagnant indicator ─
        $leadFollowupDays = (int) Setting::get('lead_followup_days', 7);

        return view('clients.index', compact('clients', 'summary', 'pipelineClients', 'conversionRate', 'leadFollowupDays'));
    }

    public function create()
    {
        return view('clients.form', [
            'client' => new Client(['status' => 'active', 'type' => 'customer']),
        ]);
    }

    public function store(ClientRequest $request)
    {
        $client = Client::create($request->validated());
        $this->logCreate($client);

        return redirect()->route('clients.show', $client)->with('success', 'Client created successfully.');
    }

    public function show(Client $client, ClientService $clientService)
    {
        // F-14 HMAC verification: validate the signature if provided
        $sig = request()->query('sig');
        if ($sig !== null) {
            $expected = self::clientHmac($client->id);
            if (! hash_equals($expected, $sig)) {
                abort(403, 'Financial data integrity check failed (F-14).');
            }
        }

        $exposure = $clientService->calculateTotalExposure($client->id);
        $overdue = $clientService->checkOverdueInvoices($client->id);
        $creditLimit = (float) $client->credit_limit;
        $usagePercent = ($creditLimit > 0) ? min(100, round(((float) $exposure / $creditLimit) * 100, 1)) : 0;

        return view('clients.show', compact('client', 'exposure', 'overdue', 'usagePercent'));
    }

    /**
     * Generate HMAC signature for a client detail link (F-14 audit compliance).
     */
    public static function clientHmac(int $clientId): string
    {
        return hash_hmac('sha256', 'client-detail:' . $clientId, config('app.key'));
    }

    /**
     * Generate HMAC signature for pipeline drag-and-drop operations (F-14).
     */
    public static function pipelineHmac(int $clientId, string $type): string
    {
        return hash_hmac('sha256', "pipeline-move:{$clientId}:{$type}", config('app.key'));
    }

    /**
     * API: Update client type via Kanban drag-and-drop.
     */
    public function updateType(Request $request, Client $client)
    {
        $request->validate([
            'type' => 'required|in:lead,prospect,customer',
            'sig'  => 'required|string',
        ]);

        $newType = $request->input('type');
        $sig = $request->input('sig');

        // F-14 HMAC verification
        $expected = self::pipelineHmac($client->id, $newType);
        if (! hash_equals($expected, $sig)) {
            return response()->json(['error' => 'Financial data integrity check failed (F-14).'], 403);
        }

        // Permission check: downgrading customer → prospect requires admin/sales_manager
        $user = $request->user();
        if ($client->type === 'customer' && $newType !== 'customer') {
            if (! $user->isAdmin() && $user->role !== 'sales_manager') {
                return response()->json([
                    'error' => __('messages.pipeline_downgrade_denied'),
                ], 403);
            }
        }

        $oldData = $client->getOriginal();
        $oldType = $client->type;
        $client->update(['type' => $newType]);

        // Audit log
        $this->logAction(
            $client,
            'update',
            "Client #{$client->id} type changed from '{$oldType}' to '{$newType}' via pipeline",
            $oldData,
        );

        return response()->json([
            'success' => true,
            'message' => __('messages.pipeline_type_updated', ['name' => $client->name, 'type' => __('messages.' . $newType)]),
        ]);
    }

    /**
     * Snooze: touch updated_at to reset the follow-up reminder timer.
     */
    public function snooze(Request $request, Client $client)
    {
        if ($client->type !== 'lead') {
            return response()->json(['error' => 'Only leads can be snoozed.'], 422);
        }

        $client->forceFill(['reminder_email_sent_at' => null])->save();
        $client->touch();

        $this->logAction(
            $client,
            'snooze',
            "Lead #{$client->id} ({$client->name}) follow-up snoozed — timer reset",
        );

        return response()->json([
            'success' => true,
            'message' => __('messages.lead_snoozed', ['name' => $client->name]),
        ]);
    }

    public function edit(Client $client)
    {
        return view('clients.form', compact('client'));
    }

    public function update(ClientRequest $request, Client $client)
    {
        $oldData = $client->getOriginal();
        $client->update($request->validated());
        $this->logUpdate($client, $oldData);

        return redirect()->route('clients.index')->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client)
    {
        $this->logDelete($client);
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client deleted successfully.');
    }
}
