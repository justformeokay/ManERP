<?php

namespace App\Http\Controllers;

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
            'pr'       => new PurchaseRequest(['priority' => 'normal', 'status' => 'draft']),
            'products' => Product::active()->orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'priority'                  => 'required|in:low,normal,high,urgent',
            'required_date'             => 'nullable|date|after_or_equal:today',
            'project_id'                => 'nullable|exists:projects,id',
            'reason'                    => 'nullable|string|max:1000',
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
        $purchaseRequest->load(['requester', 'approver', 'items.product', 'project', 'purchaseOrder']);

        return view('purchasing.requests.show', compact('purchaseRequest'));
    }

    public function edit(PurchaseRequest $purchaseRequest)
    {
        if (!in_array($purchaseRequest->status, ['draft', 'rejected'])) {
            return back()->with('error', __('messages.pr_cannot_edit'));
        }

        $purchaseRequest->load('items');

        return view('purchasing.requests.form', [
            'pr'       => $purchaseRequest,
            'products' => Product::active()->orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, PurchaseRequest $purchaseRequest)
    {
        if (!in_array($purchaseRequest->status, ['draft', 'rejected'])) {
            return back()->with('error', __('messages.pr_cannot_edit'));
        }

        $request->validate([
            'priority'                  => 'required|in:low,normal,high,urgent',
            'required_date'             => 'nullable|date|after_or_equal:today',
            'project_id'                => 'nullable|exists:projects,id',
            'reason'                    => 'nullable|string|max:1000',
            'items'                     => 'required|array|min:1',
            'items.*.product_id'        => 'required|exists:products,id',
            'items.*.quantity'          => 'required|numeric|min:0.01',
            'items.*.estimated_price'   => 'nullable|numeric|min:0',
            'items.*.specification'     => 'nullable|string|max:500',
            'items.*.notes'             => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request, $purchaseRequest) {
            $purchaseRequest->update([
                'priority'      => $request->priority,
                'required_date' => $request->required_date,
                'project_id'    => $request->project_id,
                'reason'        => $request->reason,
                'status'        => 'draft',
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

        $purchaseRequest->load('items.product');

        $suppliers  = Supplier::where('status', 'active')->orderBy('name')->get();
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('purchasing.requests.convert', compact('purchaseRequest', 'suppliers', 'warehouses'));
    }

    public function storeConversion(Request $request, PurchaseRequest $purchaseRequest)
    {
        $request->validate([
            'supplier_id'  => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'expected_date' => 'nullable|date|after_or_equal:today',
        ]);

        $po = DB::transaction(function () use ($request, $purchaseRequest) {
            $po = PurchaseOrder::create([
                'purchase_request_id' => $purchaseRequest->id,
                'supplier_id'         => $request->supplier_id,
                'warehouse_id'        => $request->warehouse_id,
                'project_id'          => $purchaseRequest->project_id,
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
