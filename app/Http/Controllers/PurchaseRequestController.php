<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Product;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Traits\Auditable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseRequestController extends Controller
{
    use Auditable;

    protected string $model = 'purchasing';

    public function index(Request $request)
    {
        $requests = PurchaseRequest::query()
            ->with(['requester', 'approver', 'items'])
            ->search($request->input('search'))
            ->when($request->input('status'), fn($q, $s) => $q->where('status', $s))
            ->when($request->input('priority'), fn($q, $p) => $q->where('priority', $p))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('purchasing.requests.index', compact('requests'));
    }

    public function create()
    {
        return view('purchasing.requests.form', [
            'pr'          => new PurchaseRequest(['priority' => 'normal', 'status' => 'draft', 'purchase_type' => 'operational']),
            'products'    => Product::active()->orderBy('name')->get(),
            'projects'    => Project::orderBy('name')->get(),
            'departments' => Department::active()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'priority'                  => 'required|in:low,normal,high,urgent',
            'purchase_type'             => 'required|in:operational,project_sales,project_capex',
            'department_id'             => 'required|exists:departments,id',
            'required_date'             => 'nullable|date|after_or_equal:today',
            'project_id'                => 'nullable|exists:projects,id',
            'reason'                    => 'required|string|max:1000',
            'items'                     => 'required|array|min:1',
            'items.*.product_id'        => 'required|exists:products,id',
            'items.*.quantity'          => 'required|numeric|min:0.01',
            'items.*.estimated_price'   => 'nullable|numeric|min:0',
            'items.*.specification'     => 'nullable|string|max:500',
            'items.*.notes'             => 'nullable|string|max:500',
        ]);

        $pr = DB::transaction(function () use ($request) {
            $pr = PurchaseRequest::create([
                'requested_by'  => Auth::id(),
                'priority'      => $request->priority,
                'purchase_type' => $request->purchase_type,
                'department_id' => $request->department_id,
                'required_date' => $request->required_date,
                'project_id'    => $request->project_id,
                'reason'        => $request->reason,
                'status'        => 'draft',
            ]);

            foreach ($request->items as $item) {
                $total = ($item['quantity'] ?? 0) * ($item['estimated_price'] ?? 0);
                $pr->items()->create(array_merge($item, ['total' => $total]));
            }

            return $pr;
        });

        $this->logCreate($pr);

        return redirect()->route('purchase-requests.index')
            ->with('success', __('messages.pr_created_success'));
    }

    public function show(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->load(['requester', 'approver', 'items.product', 'project', 'department', 'purchaseOrder']);

        return view('purchasing.requests.show', compact('purchaseRequest'));
    }

    public function edit(PurchaseRequest $purchaseRequest)
    {
        if (!in_array($purchaseRequest->status, ['draft', 'rejected'])) {
            return back()->with('error', __('messages.pr_cannot_edit'));
        }

        $purchaseRequest->load('items');

        return view('purchasing.requests.form', [
            'pr'          => $purchaseRequest,
            'products'    => Product::active()->orderBy('name')->get(),
            'projects'    => Project::orderBy('name')->get(),
            'departments' => Department::active()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, PurchaseRequest $purchaseRequest)
    {
        if (!in_array($purchaseRequest->status, ['draft', 'rejected'])) {
            return back()->with('error', __('messages.pr_cannot_edit'));
        }

        $request->validate([
            'priority'                  => 'required|in:low,normal,high,urgent',
            'purchase_type'             => 'required|in:operational,project_sales,project_capex',
            'department_id'             => 'required|exists:departments,id',
            'required_date'             => 'nullable|date|after_or_equal:today',
            'project_id'                => 'nullable|exists:projects,id',
            'reason'                    => 'required|string|max:1000',
            'items'                     => 'required|array|min:1',
            'items.*.product_id'        => 'required|exists:products,id',
            'items.*.quantity'          => 'required|numeric|min:0.01',
            'items.*.estimated_price'   => 'nullable|numeric|min:0',
            'items.*.specification'     => 'nullable|string|max:500',
            'items.*.notes'             => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request, $purchaseRequest) {
            $purchaseRequest->update([
                'priority'         => $request->priority,
                'purchase_type'    => $request->purchase_type,
                'department_id'    => $request->department_id,
                'required_date'    => $request->required_date,
                'project_id'       => $request->project_id,
                'reason'           => $request->reason,
                'status'           => 'draft',
                'rejection_reason' => null,
            ]);

            $purchaseRequest->items()->delete();
            foreach ($request->items as $item) {
                $total = ($item['quantity'] ?? 0) * ($item['estimated_price'] ?? 0);
                $purchaseRequest->items()->create(array_merge($item, ['total' => $total]));
            }
        });

        return redirect()->route('purchase-requests.show', $purchaseRequest)
            ->with('success', __('messages.pr_updated_success'));
    }

    public function submit(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->transitionToAndSave('pending');

        return back()->with('success', __('messages.pr_submitted'));
    }

    public function approve(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->transitionTo('approved');
        $purchaseRequest->update([
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', __('messages.pr_approved'));
    }

    public function reject(Request $request, PurchaseRequest $purchaseRequest)
    {
        $request->validate(['rejection_reason' => 'required|string|max:500']);

        $purchaseRequest->transitionTo('rejected');
        $purchaseRequest->update([
            'rejection_reason' => $request->rejection_reason,
            'rejected_at'      => now(),
        ]);

        return back()->with('success', __('messages.pr_rejected'));
    }

    /**
     * Convert approved Purchase Request into a Purchase Order.
     */
    public function convertToPo(PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->status !== 'approved') {
            return back()->with('error', __('messages.pr_must_be_approved'));
        }

        // Duplicate prevention: if already converted (has linked PO)
        if ($purchaseRequest->purchaseOrder) {
            return back()->with('error', __('messages.pr_already_converted'));
        }

        $purchaseRequest->load('items.product', 'department');

        $suppliers   = Supplier::where('status', 'active')->orderBy('name')->get();
        $warehouses  = Warehouse::where('is_active', true)->orderBy('name')->get();
        $departments = Department::active()->orderBy('name')->get();

        // HMAC signature for conversion integrity (F-14)
        $conversionSig = PurchaseRequest::conversionHmac($purchaseRequest->id);

        return view('purchasing.requests.convert', compact(
            'purchaseRequest', 'suppliers', 'warehouses', 'departments', 'conversionSig'
        ));
    }

    public function storeConversion(Request $request, PurchaseRequest $purchaseRequest)
    {
        // Status guard
        if ($purchaseRequest->status !== 'approved') {
            return back()->with('error', __('messages.pr_must_be_approved'));
        }

        // Duplicate conversion guard
        if ($purchaseRequest->purchaseOrder) {
            return back()->with('error', __('messages.pr_already_converted'));
        }

        // HMAC integrity check (F-14 audit finding)
        $expectedSig = PurchaseRequest::conversionHmac($purchaseRequest->id);
        if (! hash_equals($expectedSig, $request->input('conversion_sig', ''))) {
            abort(403, 'Financial data integrity check failed (F-14).');
        }

        $request->validate([
            'supplier_id'       => 'required|exists:suppliers,id',
            'warehouse_id'      => 'required|exists:warehouses,id',
            'department_id'     => 'required|exists:departments,id',
            'payment_terms'     => 'required|in:cash,cod,net_15,net_30,net_60,net_90',
            'shipping_address'  => 'nullable|string|max:500',
            'expected_date'     => 'nullable|date|after_or_equal:today',
            'budget_override'   => 'nullable|boolean',
        ]);

        $purchaseRequest->load('items');

        $po = DB::transaction(function () use ($request, $purchaseRequest) {
            $po = PurchaseOrder::create([
                'purchase_request_id' => $purchaseRequest->id,
                'supplier_id'         => $request->supplier_id,
                'warehouse_id'        => $request->warehouse_id,
                'project_id'          => $purchaseRequest->project_id,
                'purchase_type'       => $purchaseRequest->purchase_type ?? 'operational',
                'department_id'       => $request->department_id,
                'priority'            => $purchaseRequest->priority,
                'payment_terms'       => $request->payment_terms,
                'shipping_address'    => $request->shipping_address,
                'status'              => 'draft',
                'order_date'          => now()->toDateString(),
                'expected_date'       => $request->expected_date,
                'created_by'          => Auth::id(),
            ]);

            foreach ($purchaseRequest->items as $item) {
                $po->items()->create([
                    'product_id'  => $item->product_id,
                    'quantity'    => $item->quantity,
                    'unit_price'  => $item->estimated_price,
                    'total'       => $item->total,
                ]);
            }

            $po->recalculateTotals();
            $purchaseRequest->transitionToAndSave('converted');

            return $po;
        });

        // Audit log for conversion action
        $this->logAction($purchaseRequest, 'convert_to_po',
            "PR #{$purchaseRequest->number} converted to PO #{$po->number}",
            ['pr_id' => $purchaseRequest->id, 'pr_number' => $purchaseRequest->number]
        );

        return redirect()->route('purchasing.show', $po)
            ->with('success', __('messages.pr_converted_to_po', ['po' => $po->number]));
    }

    public function destroy(PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->status !== 'draft') {
            return back()->with('error', __('messages.pr_only_delete_draft'));
        }

        $this->logDelete($purchaseRequest);
        $purchaseRequest->delete();

        return redirect()->route('purchase-requests.index')
            ->with('success', __('messages.pr_deleted'));
    }
}
