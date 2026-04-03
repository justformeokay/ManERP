<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalaryStructureRequest;
use App\Models\Employee;
use App\Models\SalaryStructure;
use App\Traits\Auditable;

class SalaryStructureController extends Controller
{
    use Auditable;

    protected string $model = 'hr';

    public function store(SalaryStructureRequest $request)
    {
        $employee = Employee::findOrFail($request->employee_id);

        // Deactivate previous structures
        $employee->salaryStructures()
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $salary = SalaryStructure::create(
            array_merge($request->validated(), ['is_active' => true])
        );

        $this->logCreate($salary, 'hr');

        return redirect()->route('hr.employees.show', $employee)
            ->with('success', 'Struktur gaji berhasil disimpan.');
    }
}
