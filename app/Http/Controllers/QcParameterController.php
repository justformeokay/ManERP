<?php

namespace App\Http\Controllers;

use App\Http\Requests\QcParameterRequest;
use App\Models\QcParameter;
use App\Traits\Auditable;
use Illuminate\Http\Request;

class QcParameterController extends Controller
{
    use Auditable;

    protected string $model = 'quality_control';

    public function index(Request $request)
    {
        $parameters = QcParameter::query()
            ->search($request->input('search'))
            ->when($request->input('type'), fn($q, $t) => $q->where('type', $t))
            ->when($request->has('active'), fn($q) => $q->where('is_active', $request->boolean('active')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('quality-control.parameters.index', compact('parameters'));
    }

    public function create()
    {
        return view('quality-control.parameters.form', [
            'parameter' => new QcParameter(['is_active' => true, 'type' => 'numeric']),
        ]);
    }

    public function store(QcParameterRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $parameter = QcParameter::create($data);
        $this->logCreate($parameter);

        return redirect()->route('qc.parameters.index')
            ->with('success', __('messages.qc_parameter_created'));
    }

    public function edit(QcParameter $parameter)
    {
        return view('quality-control.parameters.form', compact('parameter'));
    }

    public function update(QcParameterRequest $request, QcParameter $parameter)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $oldData = $parameter->getOriginal();

        $parameter->update($data);
        $this->logUpdate($parameter, $oldData);

        return redirect()->route('qc.parameters.index')
            ->with('success', __('messages.qc_parameter_updated'));
    }

    public function destroy(QcParameter $parameter)
    {
        $this->logDelete($parameter);
        $parameter->delete();

        return redirect()->route('qc.parameters.index')
            ->with('success', __('messages.qc_parameter_deleted'));
    }
}
