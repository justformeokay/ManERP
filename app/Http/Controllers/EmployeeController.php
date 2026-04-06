<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeRequest;
use App\Exports\EmployeeTemplateExport;
use App\Imports\EmployeeImport;
use App\Models\Bank;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Shift;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

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
        $departments = Department::active()->orderBy('name')->get();
        $positions = Position::active()->orderBy('name')->get();
        return view('hr.employees.create', compact('availableUsers', 'shifts', 'banks', 'departments', 'positions'));
    }

    public function store(EmployeeRequest $request)
    {
        $data = $request->validated();
        if (!empty($data['bank_id']) && empty($data['bank_name'])) {
            $data['bank_name'] = Bank::find($data['bank_id'])?->name;
        }
        $employee = Employee::create($data);

        $this->logCreate($employee, 'hr');

        return redirect()->route('hr.employees.index')
            ->with('success', "Karyawan {$employee->name} berhasil ditambahkan.");
    }

    public function show(Employee $employee)
    {
        $employee->load(['salaryStructures' => fn($q) => $q->latest('effective_date'), 'payslips.payrollPeriod', 'bank']);

        return view('hr.employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $availableUsers = $this->getAvailableUsers($employee->user_id);
        $shifts = Shift::active()->orderBy('name')->get();
        $banks = Bank::active()->orderBy('name')->get();
        $departments = Department::active()->orderBy('name')->get();
        $positions = Position::active()->orderBy('name')->get();
        return view('hr.employees.edit', compact('employee', 'availableUsers', 'shifts', 'banks', 'departments', 'positions'));
    }

    public function update(EmployeeRequest $request, Employee $employee)
    {
        $data = $request->validated();
        if (!empty($data['bank_id']) && empty($data['bank_name'])) {
            $data['bank_name'] = Bank::find($data['bank_id'])?->name;
        }
        $oldData = $employee->toArray();
        $employee->update($data);
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
     * Download employee import template (.xlsx).
     */
    public function downloadTemplate()
    {
        return Excel::download(new EmployeeTemplateExport(), 'template_import_karyawan.xlsx');
    }

    /**
     * Import employees from uploaded Excel/CSV file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getSheet(0); // First sheet only
        $rows = $worksheet->toArray(null, true, true, false);

        // Remove header row
        $header = array_shift($rows);

        // Filter out completely empty rows
        $rows = array_values(array_filter($rows, fn ($row) => !empty(array_filter($row, fn ($cell) => $cell !== null && trim((string) $cell) !== ''))));

        $importer = new EmployeeImport();
        $success = $importer->process($rows);

        if ($success) {
            return redirect()->route('hr.employees.index')
                ->with('success', __('messages.import_success', ['count' => $importer->getImportedCount()]));
        }

        return redirect()->route('hr.employees.index')
            ->with('import_errors', $importer->getErrors())
            ->with('error', __('messages.import_failed'));
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
