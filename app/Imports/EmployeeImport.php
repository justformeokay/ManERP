<?php

namespace App\Imports;

use App\Models\Bank;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Setting;
use App\Models\Shift;
use App\Services\AuditLogService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EmployeeImport
{
    /** @var array<int, string> Collected errors keyed by row number */
    private array $errors = [];

    /** @var int Successfully imported count */
    private int $importedCount = 0;

    /** Cached lookup maps (built once) */
    private Collection $departmentMap;
    private Collection $positionMap;
    private Collection $shiftMap;
    private Collection $bankMap;

    /** Dynamic validation lengths */
    private int $nikMin;
    private int $nikMax;
    private int $bankAcctMin;
    private int $bankAcctMax;

    public function __construct()
    {
        // Build case-insensitive lookup maps: lowercase name → model
        $this->departmentMap = Department::active()->get()->keyBy(fn ($d) => mb_strtolower($d->name));
        $this->positionMap   = Position::active()->get()->keyBy(fn ($p) => mb_strtolower($p->name));
        $this->shiftMap      = Shift::active()->get()->keyBy(fn ($s) => mb_strtolower($s->name));
        $this->bankMap       = Bank::active()->get()->keyBy(fn ($b) => mb_strtolower($b->name));

        $this->nikMin     = (int) Setting::get('nik_min_length', 1);
        $this->nikMax     = (int) Setting::get('nik_max_length', 20);
        $this->bankAcctMin = (int) Setting::get('bank_account_min_length', 1);
        $this->bankAcctMax = (int) Setting::get('bank_account_max_length', 30);
    }

    /**
     * Process import from a parsed array of rows (header row excluded).
     *
     * @param  array<int, array>  $rows  Each row is an associative array keyed by column header
     * @return bool True if import succeeded, false if errors occurred
     */
    public function process(array $rows): bool
    {
        $this->errors = [];
        $this->importedCount = 0;

        if (empty($rows)) {
            $this->errors[0] = __('messages.import_empty_file');
            return false;
        }

        // Pre-collect existing NIKs for duplicate check
        $existingNiks = Employee::pluck('nik')->map(fn ($n) => mb_strtolower(trim($n)))->toArray();
        $importNiks = [];

        // Phase 1: Validate all rows before any DB writes
        $preparedRows = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // Excel row (1-indexed + header)
            $rowErrors = [];

            // Normalize cell values
            $nik          = trim((string) ($row[0] ?? ''));
            $name         = trim((string) ($row[1] ?? ''));
            $positionName = trim((string) ($row[2] ?? ''));
            $deptName     = trim((string) ($row[3] ?? ''));
            $shiftName    = trim((string) ($row[4] ?? ''));
            $joinDate     = trim((string) ($row[5] ?? ''));
            $status       = mb_strtolower(trim((string) ($row[6] ?? '')));
            $npwp         = trim((string) ($row[7] ?? ''));
            $ptkp         = trim((string) ($row[8] ?? ''));
            $terCategory  = strtoupper(trim((string) ($row[9] ?? '')));
            $bpjsTk       = trim((string) ($row[10] ?? ''));
            $bpjsKes      = trim((string) ($row[11] ?? ''));
            $bankName     = trim((string) ($row[12] ?? ''));
            $bankAcctNum  = trim((string) ($row[13] ?? ''));
            $bankAcctName = trim((string) ($row[14] ?? ''));

            // Skip completely empty rows
            if ($nik === '' && $name === '') {
                continue;
            }

            // --- Basic validation ---
            $validator = Validator::make([
                'nik'                 => $nik,
                'name'                => $name,
                'join_date'           => $joinDate,
                'status'              => $status,
                'ptkp_status'         => $ptkp,
                'npwp'                => $npwp ?: null,
                'bpjs_tk_number'      => $bpjsTk ?: null,
                'bpjs_kes_number'     => $bpjsKes ?: null,
                'bank_account_number' => $bankAcctNum ?: null,
                'bank_account_name'   => $bankAcctName ?: null,
            ], [
                'nik'                 => ['required', 'string', "min:{$this->nikMin}", "max:{$this->nikMax}"],
                'name'                => ['required', 'string', 'max:255'],
                'join_date'           => ['required', 'date', 'date_format:Y-m-d'],
                'status'              => ['required', 'in:' . implode(',', Employee::statusOptions())],
                'ptkp_status'         => ['required', 'in:' . implode(',', Employee::ptkpOptions())],
                'npwp'                => ['nullable', 'string', 'max:30'],
                'bpjs_tk_number'      => ['nullable', 'string', 'max:30'],
                'bpjs_kes_number'     => ['nullable', 'string', 'max:30'],
                'bank_account_number' => ['nullable', 'string', "min:{$this->bankAcctMin}", "max:{$this->bankAcctMax}"],
                'bank_account_name'   => ['nullable', 'string', 'max:255'],
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $msg) {
                    $rowErrors[] = $msg;
                }
            }

            // --- NIK uniqueness (DB + within file) ---
            $nikLower = mb_strtolower($nik);
            if (in_array($nikLower, $existingNiks, true)) {
                $rowErrors[] = __('messages.import_nik_exists', ['nik' => $nik]);
            }
            if (isset($importNiks[$nikLower])) {
                $rowErrors[] = __('messages.import_nik_duplicate_in_file', ['nik' => $nik, 'row' => $importNiks[$nikLower]]);
            }
            $importNiks[$nikLower] = $rowNumber;

            // --- Smart mapping: Department ---
            $departmentId = null;
            $departmentStr = null;
            if ($deptName !== '') {
                $dept = $this->departmentMap->get(mb_strtolower($deptName));
                if ($dept) {
                    $departmentStr = $dept->name;
                } else {
                    $rowErrors[] = __('messages.import_department_not_found', ['name' => $deptName]);
                }
            }

            // --- Smart mapping: Position ---
            $positionStr = null;
            if ($positionName !== '') {
                $pos = $this->positionMap->get(mb_strtolower($positionName));
                if ($pos) {
                    $positionStr = $pos->name;
                } else {
                    $rowErrors[] = __('messages.import_position_not_found', ['name' => $positionName]);
                }
            }

            // --- Smart mapping: Shift ---
            $shiftId = null;
            if ($shiftName !== '') {
                $shift = $this->shiftMap->get(mb_strtolower($shiftName));
                if ($shift) {
                    $shiftId = $shift->id;
                } else {
                    $rowErrors[] = __('messages.import_shift_not_found', ['name' => $shiftName]);
                }
            }

            // --- Smart mapping: Bank ---
            $bankId = null;
            $bankStr = null;
            if ($bankName !== '') {
                $bank = $this->bankMap->get(mb_strtolower($bankName));
                if ($bank) {
                    $bankId = $bank->id;
                    $bankStr = $bank->name;
                } else {
                    $rowErrors[] = __('messages.import_bank_not_found', ['name' => $bankName]);
                }
            }

            if (!empty($rowErrors)) {
                $this->errors[$rowNumber] = implode('; ', $rowErrors);
                continue;
            }

            $preparedRows[] = [
                'nik'                 => $nik,
                'name'                => $name,
                'position'            => $positionStr,
                'department'          => $departmentStr,
                'shift_id'            => $shiftId,
                'join_date'           => $joinDate,
                'status'              => $status,
                'npwp'                => $npwp ?: null,
                'ptkp_status'         => $ptkp,
                'ter_category'        => $terCategory ?: null,
                'bpjs_tk_number'      => $bpjsTk ?: null,
                'bpjs_kes_number'     => $bpjsKes ?: null,
                'bank_id'             => $bankId,
                'bank_name'           => $bankStr,
                'bank_account_number' => $bankAcctNum ?: null,
                'bank_account_name'   => $bankAcctName ?: null,
            ];
        }

        // If any validation errors, abort entirely
        if (!empty($this->errors)) {
            return false;
        }

        if (empty($preparedRows)) {
            $this->errors[0] = __('messages.import_no_valid_rows');
            return false;
        }

        // Phase 2: Transaction-based insert with audit trail
        DB::transaction(function () use ($preparedRows) {
            foreach ($preparedRows as $data) {
                $employee = Employee::create($data);

                AuditLogService::log(
                    'hr',
                    'create',
                    "Imported employee: {$employee->name} ({$employee->nik})",
                    null,
                    $employee->toArray(),
                    $employee
                );

                $this->importedCount++;
            }
        });

        return true;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }
}
