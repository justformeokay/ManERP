<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeRequest;
use App\Models\Bank;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\User;
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
        $availableUsers = $this->getAvailableUsers();
        $shifts = Shift::active()->orderBy('name')->get();
        $banks = Bank::active()->orderBy('name')->get();
        return view('hr.employees.create', compact('availableUsers', 'shifts', 'banks'));
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
        $availableUsers = $this->getAvailableUsers($employee->user_id);
        $shifts = Shift::active()->orderBy('name')->get();
        $banks = Bank::active()->orderBy('name')->get();
        return view('hr.employees.edit', compact('employee', 'availableUsers', 'shifts', 'banks'));
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

    /**
     * Get users that are not yet linked to any employee.
     * Optionally include the currently linked user_id (for edit form).
     */
    private function getAvailableUsers(?int $currentUserId = null): \Illuminate\Support\Collection
    {
        $linkedUserIds = Employee::whereNotNull('user_id')
            ->when($currentUserId, fn($q) => $q->where('user_id', '!=', $currentUserId))
            ->pluck('user_id');

        return User::where('status', User::STATUS_ACTIVE)
            ->whereNotIn('id', $linkedUserIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);
    }
}
