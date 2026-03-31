<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Services\StockService;
use App\Traits\Auditable;
use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    use Auditable;

    protected string $model = 'inventory';

    public function __construct(private StockService $stockService) {}

    public function index(Request $request)
    {
        $transfers = StockTransfer::query()
            ->with(['product', 'fromWarehouse', 'toWarehouse', 'creator'])
            ->search($request->input('search'))
            ->when($request->input('status'), fn($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('inventory.transfers.index', compact('transfers'));
    }

    public function create()
    {
        return view('inventory.transfers.form', [
            'transfer'   => new StockTransfer(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
            'products'   => Product::active()->with('inventoryStocks')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id'        => ['required', 'exists:products,id'],
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id'   => ['required', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'quantity'          => ['required', 'numeric', 'min:0.01'],
            'notes'             => ['nullable', 'string', 'max:1000'],
            'execute'           => ['nullable', 'boolean'],
        ]);

        $transfer = StockTransfer::create([
            'product_id'        => $data['product_id'],
            'from_warehouse_id' => $data['from_warehouse_id'],
            'to_warehouse_id'   => $data['to_warehouse_id'],
            'quantity'          => $data['quantity'],
            'notes'             => $data['notes'] ?? null,
            'status'            => 'pending',
        ]);

        // Execute immediately if requested
        if ($request->boolean('execute')) {
            try {
                $this->stockService->executeTransfer($transfer);
                $this->logAction($transfer, 'transfer', "Transfer {$transfer->number} created and executed");
                return redirect()
                    ->route('inventory.transfers.index')
                    ->with('success', "Transfer {$transfer->number} created and completed.");
            } catch (\InvalidArgumentException $e) {
                $transfer->delete();
                return back()->withInput()->withErrors(['quantity' => $e->getMessage()]);
            }
        }

        $this->logCreate($transfer);

        return redirect()
            ->route('inventory.transfers.index')
            ->with('success', "Transfer {$transfer->number} created as pending.");
    }

    public function execute(StockTransfer $transfer)
    {
        if ($transfer->status !== 'pending') {
            return back()->with('error', 'Only pending transfers can be executed.');
        }

        try {
            $oldData = $transfer->toArray();
            $this->stockService->executeTransfer($transfer);
            $this->logAction($transfer, 'transfer', "Transfer {$transfer->number} executed", $oldData);
            return back()->with('success', "Transfer {$transfer->number} completed successfully.");
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['stock' => $e->getMessage()]);
        }
    }

    public function cancel(StockTransfer $transfer)
    {
        if ($transfer->status === 'cancelled') {
            return back()->with('error', 'Transfer is already cancelled.');
        }

        try {
            if ($transfer->status === 'completed') {
                $oldData = $transfer->toArray();
                $this->stockService->reverseTransfer($transfer);
                $this->logAction($transfer, 'cancel', "Transfer {$transfer->number} cancelled and reversed", $oldData);
                return back()->with('success', "Transfer {$transfer->number} cancelled and stock reversed.");
            }

            $oldData = $transfer->toArray();
            $transfer->update(['status' => 'cancelled']);
            $this->logAction($transfer, 'cancel', "Transfer {$transfer->number} cancelled", $oldData);
            return back()->with('success', "Transfer {$transfer->number} cancelled.");
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['stock' => $e->getMessage()]);
        }
    }

    public function destroy(StockTransfer $transfer)
    {
        if ($transfer->status !== 'pending') {
            return back()->with('error', 'Only pending transfers can be deleted.');
        }

        $this->logDelete($transfer);
        $transfer->delete();

        return redirect()
            ->route('inventory.transfers.index')
            ->with('success', 'Transfer deleted.');
    }
}
