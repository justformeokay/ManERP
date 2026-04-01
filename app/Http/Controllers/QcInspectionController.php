<?php

namespace App\Http\Controllers;

use App\Http\Requests\QcInspectionRequest;
use App\Models\ManufacturingOrder;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\QcInspection;
use App\Models\QcInspectionItem;
use App\Models\QcParameter;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use App\Traits\Auditable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QcInspectionController extends Controller
{
    use Auditable;

    protected string $model = 'quality_control';

    public function index(Request $request)
    {
        $inspections = QcInspection::query()
            ->with(['product', 'inspector', 'warehouse'])
            ->search($request->input('search'))
            ->when($request->input('type'), fn($q, $t) => $q->where('inspection_type', $t))
            ->when($request->input('result'), fn($q, $r) => $q->where('result', $r))
            ->when($request->input('status'), fn($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('quality-control.inspections.index', compact('inspections'));
    }

    public function create(Request $request)
    {
        $inspection = new QcInspection([
            'status'          => 'draft',
            'result'          => 'pending',
            'inspection_type' => $request->input('type', 'incoming'),
        ]);

        return view('quality-control.inspections.form', [
            'inspection' => $inspection,
            'parameters' => QcParameter::active()->orderBy('name')->get(),
            'products'   => Product::orderBy('name')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
        ]);
    }

    public function store(QcInspectionRequest $request)
    {
        $data = $request->validated();
        $data['inspector_id'] = auth()->id();
        $items = $data['items'] ?? [];
        unset($data['items']);

        $inspection = DB::transaction(function () use ($data, $items) {
            $inspection = QcInspection::create($data);

            foreach ($items as $item) {
                $inspection->items()->create([
                    'qc_parameter_id' => $item['qc_parameter_id'],
                    'min_value'       => $item['min_value'] ?? null,
                    'max_value'       => $item['max_value'] ?? null,
                    'result'          => 'pending',
                ]);
            }

            return $inspection;
        });

        $this->logCreate($inspection);

        return redirect()->route('qc.inspections.show', $inspection)
            ->with('success', __('messages.qc_inspection_created'));
    }

    public function show(QcInspection $inspection)
    {
        $inspection->load(['product', 'warehouse', 'inspector', 'items.parameter', 'reference']);

        return view('quality-control.inspections.show', compact('inspection'));
    }

    public function edit(QcInspection $inspection)
    {
        $check = $inspection->requireStatus(['draft', 'in_progress']);
        if ($check !== true) {
            return back()->withErrors(['status' => $check]);
        }

        $inspection->load('items');

        return view('quality-control.inspections.form', [
            'inspection' => $inspection,
            'parameters' => QcParameter::active()->orderBy('name')->get(),
            'products'   => Product::orderBy('name')->get(),
            'warehouses' => Warehouse::active()->orderBy('name')->get(),
        ]);
    }

    public function update(QcInspectionRequest $request, QcInspection $inspection)
    {
        $check = $inspection->requireStatus(['draft', 'in_progress']);
        if ($check !== true) {
            return back()->withErrors(['status' => $check]);
        }

        $data = $request->validated();
        $items = $data['items'] ?? [];
        unset($data['items']);

        $oldData = $inspection->getOriginal();

        DB::transaction(function () use ($inspection, $data, $items) {
            $inspection->update($data);
            $inspection->items()->delete();

            foreach ($items as $item) {
                $inspection->items()->create([
                    'qc_parameter_id' => $item['qc_parameter_id'],
                    'min_value'       => $item['min_value'] ?? null,
                    'max_value'       => $item['max_value'] ?? null,
                    'result'          => 'pending',
                ]);
            }
        });

        $this->logUpdate($inspection, $oldData);

        return redirect()->route('qc.inspections.show', $inspection)
            ->with('success', __('messages.qc_inspection_updated'));
    }

    /**
     * Record inspection results for each parameter check.
     */
    public function recordResults(Request $request, QcInspection $inspection)
    {
        $request->validate([
            'results'                  => ['required', 'array'],
            'results.*.item_id'        => ['required', 'exists:qc_inspection_items,id'],
            'results.*.measured_value' => ['nullable', 'string'],
            'results.*.result'         => ['required', 'in:pass,fail'],
            'results.*.notes'          => ['nullable', 'string', 'max:500'],
            'passed_quantity'          => ['required', 'numeric', 'min:0'],
            'failed_quantity'          => ['required', 'numeric', 'min:0'],
        ]);

        $oldData = $inspection->toArray();

        DB::transaction(function () use ($request, $inspection) {
            // Update each inspection item
            foreach ($request->input('results') as $result) {
                QcInspectionItem::where('id', $result['item_id'])
                    ->where('qc_inspection_id', $inspection->id)
                    ->update([
                        'measured_value' => $result['measured_value'] ?? null,
                        'result'         => $result['result'],
                        'notes'          => $result['notes'] ?? null,
                    ]);
            }

            // Update inspection totals and result
            $passedQty = $request->input('passed_quantity');
            $failedQty = $request->input('failed_quantity');

            $inspection->update([
                'passed_quantity' => $passedQty,
                'failed_quantity' => $failedQty,
                'status'          => 'completed',
                'inspected_at'    => now(),
                'result'          => $this->determineOverallResult($inspection, $passedQty, $failedQty),
            ]);
        });

        $this->logAction($inspection, 'record_results', "QC inspection {$inspection->number} results recorded", $oldData);

        return redirect()->route('qc.inspections.show', $inspection)
            ->with('success', __('messages.qc_results_recorded'));
    }

    public function destroy(QcInspection $inspection)
    {
        $check = $inspection->requireStatus(['draft', 'in_progress']);
        if ($check !== true) {
            return back()->withErrors(['status' => $check]);
        }

        $this->logDelete($inspection);
        $inspection->delete();

        return redirect()->route('qc.inspections.index')
            ->with('success', __('messages.qc_inspection_deleted'));
    }

    private function determineOverallResult(QcInspection $inspection, float $passedQty, float $failedQty): string
    {
        if ($failedQty <= 0) {
            return 'passed';
        }
        if ($passedQty <= 0) {
            return 'failed';
        }
        return 'partial';
    }
}
