<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeRequest;
use App\Models\Employee;
use App\Traits\Auditable;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    use Auditable;

    protected string $model = 'hr';

    public function index(Request $request)
    {
        $employees = Employee::query()
            ->search($request->input('search'))
            ->when($request->input('department'), fn($q, $d) => $q->where('department', $d))
            ->when($request->input('status'), fn($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $departments = Employee::whereNotNull('department')
            ->distinct()
            ->pluck('department')
            ->sort()
            ->values();

        return view('hr.employees.index', compact('employees', 'departments'));
    }

    public function create()
    {
        return view('hr.employees.create');
    }

    public function store(EmployeeRequest $request)
    {
        $employee = Employee::create($request->validated());

        $this->logCreate($employee, 'hr');

        return redirect()->route('hr.employees.index')
            ->with('success', "Karyawan {$employee->name} berhasil ditambahkan.");
    }

    public function show(Employee $employee)
    {
        $employee->load(['salaryStructures' => fn($q) => $q->latest('effective_date'), 'payslips.payrollPeriod']);

        return view('hr.employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        return view('hr.employees.edit', compact('employee'));
    }

    public function update(EmployeeRequest $request, Employee $employee)
    {
        $oldData = $employee->toArray();
        $employee->update($request->validated());
        $this->logUpdate($employee, $oldData, 'hr');

        return redirect()->route('hr.employees.show', $employee)
            ->with('success', "Data karyawan {$employee->name} berhasil diperbarui.");
    }

    public function destroy(Employee $employee)
    {
        $this->logDelete($employee, 'hr');
        $employee->delete();

        return redirect()->route('hr.employees.index')
            ->with('success', 'Karyawan berhasil dihapus.');
    }
}
