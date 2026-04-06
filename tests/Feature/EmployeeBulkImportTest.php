<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Bank;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\Password;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class EmployeeBulkImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Password::defaults(fn () => Password::min(8));

        $this->admin = User::factory()->create([
            'role'   => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // TEMPLATE DOWNLOAD
    // ═══════════════════════════════════════════════════════════════

    public function test_admin_can_download_employee_template(): void
    {
        $response = $this->actingAs($this->admin)->get(route('hr.employees.template'));

        $response->assertOk();
        $response->assertDownload('template_import_karyawan.xlsx');
    }

    public function test_template_has_correct_headers(): void
    {
        $response = $this->actingAs($this->admin)->get(route('hr.employees.template'));

        // BinaryFileResponse - get the actual file path
        $tmpFile = $response->baseResponse->getFile()->getPathname();

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmpFile);
        $sheet = $spreadsheet->getSheetByName('Template');

        $this->assertNotNull($sheet);
        $this->assertEquals('NIK', $sheet->getCell('A1')->getValue());
        $this->assertEquals('Nama', $sheet->getCell('B1')->getValue());
        $this->assertEquals('Jabatan', $sheet->getCell('C1')->getValue());
        $this->assertEquals('Departemen', $sheet->getCell('D1')->getValue());

        // Instructions sheet should exist
        $instructions = $spreadsheet->getSheetByName('Instructions');
        $this->assertNotNull($instructions);
    }

    // ═══════════════════════════════════════════════════════════════
    // SUCCESSFUL IMPORT
    // ═══════════════════════════════════════════════════════════════

    public function test_successful_bulk_import_creates_employees(): void
    {
        $dept = Department::create(['name' => 'IT', 'code' => 'IT', 'is_active' => true]);
        $pos = Position::create(['name' => 'Staff IT', 'code' => 'SIT', 'is_active' => true]);
        $bank = Bank::create(['name' => 'BCA', 'code' => 'BCA', 'is_active' => true]);

        $file = $this->createImportFile([
            ['EMP-IMPORT-001', 'Budi Santoso', 'Staff IT', 'IT', '', '2024-01-15', 'active', '', 'TK/0', '', '', '', 'BCA', '1234567890', 'Budi Santoso'],
            ['EMP-IMPORT-002', 'Siti Rahayu', 'Staff IT', 'IT', '', '2024-02-01', 'active', '', 'K/0', '', '', '', '', '', ''],
        ]);

        $response = $this->actingAs($this->admin)->post(route('hr.employees.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('hr.employees.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('employees', ['nik' => 'EMP-IMPORT-001', 'name' => 'Budi Santoso']);
        $this->assertDatabaseHas('employees', ['nik' => 'EMP-IMPORT-002', 'name' => 'Siti Rahayu']);
        $this->assertEquals(2, Employee::where('nik', 'like', 'EMP-IMPORT-%')->count());
    }

    public function test_import_resolves_department_case_insensitive(): void
    {
        Department::create(['name' => 'Finance', 'code' => 'FIN', 'is_active' => true]);

        $file = $this->createImportFile([
            ['EMP-CS-001', 'Test User', '', 'finance', '', '2024-01-01', 'active', '', 'TK/0', '', '', '', '', '', ''],
        ]);

        $this->actingAs($this->admin)->post(route('hr.employees.import'), ['file' => $file]);

        $this->assertDatabaseHas('employees', ['nik' => 'EMP-CS-001', 'department' => 'Finance']);
    }

    public function test_import_resolves_bank_and_sets_bank_id(): void
    {
        $bank = Bank::create(['name' => 'BNI', 'code' => 'BNI', 'is_active' => true]);

        $file = $this->createImportFile([
            ['EMP-BK-001', 'Bank Test', '', '', '', '2024-01-01', 'active', '', 'TK/0', '', '', '', 'BNI', '9876543210', 'Bank Test'],
        ]);

        $this->actingAs($this->admin)->post(route('hr.employees.import'), ['file' => $file]);

        $emp = Employee::where('nik', 'EMP-BK-001')->first();
        $this->assertNotNull($emp);
        $this->assertEquals($bank->id, $emp->bank_id);
        $this->assertEquals('BNI', $emp->bank_name);
    }

    // ═══════════════════════════════════════════════════════════════
    // AUDIT TRAIL
    // ═══════════════════════════════════════════════════════════════

    public function test_each_imported_employee_has_audit_log(): void
    {
        $file = $this->createImportFile([
            ['EMP-AUD-001', 'Audit One', '', '', '', '2024-01-01', 'active', '', 'TK/0', '', '', '', '', '', ''],
            ['EMP-AUD-002', 'Audit Two', '', '', '', '2024-02-01', 'active', '', 'TK/1', '', '', '', '', '', ''],
        ]);

        $this->actingAs($this->admin)->post(route('hr.employees.import'), ['file' => $file]);

        $logs = ActivityLog::where('module', 'hr')->where('action', 'create')
            ->where('description', 'like', 'Imported employee:%')->get();

        $this->assertCount(2, $logs);
    }

    public function test_imported_audit_logs_have_valid_hmac_checksum(): void
    {
        $file = $this->createImportFile([
            ['EMP-HMAC-001', 'HMAC Test', '', '', '', '2024-01-01', 'active', '', 'TK/0', '', '', '', '', '', ''],
        ]);

        $this->actingAs($this->admin)->post(route('hr.employees.import'), ['file' => $file]);

        $log = ActivityLog::where('description', 'like', '%HMAC Test%')->first();
        $this->assertNotNull($log);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $log->checksum);
        $this->assertTrue(AuditLogService::verifyChecksum($log));
    }

    // ═══════════════════════════════════════════════════════════════
    // VALIDATION ERRORS
    // ═══════════════════════════════════════════════════════════════

    public function test_import_fails_with_duplicate_nik_in_database(): void
    {
        Employee::factory()->create(['nik' => 'EXISTING-NIK']);

        $file = $this->createImportFile([
            ['EXISTING-NIK', 'Duplicate', '', '', '', '2024-01-01', 'active', '', 'TK/0', '', '', '', '', '', ''],
        ]);

        $response = $this->actingAs($this->admin)->post(route('hr.employees.import'), ['file' => $file]);

        $response->assertRedirect();
        $response->assertSessionHas('import_errors');
        $response->assertSessionHas('error');

        // Employee count shouldn't increase
        $this->assertEquals(1, Employee::where('nik', 'EXISTING-NIK')->count());
    }

    public function test_import_fails_with_duplicate_nik_within_file(): void
    {
        $file = $this->createImportFile([
            ['DUPE-IN-FILE', 'First', '', '', '', '2024-01-01', 'active', '', 'TK/0', '', '', '', '', '', ''],
            ['DUPE-IN-FILE', 'Second', '', '', '', '2024-02-01', 'active', '', 'TK/0', '', '', '', '', '', ''],
        ]);

        $response = $this->actingAs($this->admin)->post(route('hr.employees.import'), ['file' => $file]);

        $response->assertSessionHas('import_errors');
        $this->assertEquals(0, Employee::where('nik', 'DUPE-IN-FILE')->count());
    }

    public function test_import_fails_with_unknown_department(): void
    {
        $file = $this->createImportFile([
            ['EMP-NODPT-001', 'No Dept', '', 'NonExistent', '', '2024-01-01', 'active', '', 'TK/0', '', '', '', '', '', ''],
        ]);

        $response = $this->actingAs($this->admin)->post(route('hr.employees.import'), ['file' => $file]);

        $response->assertSessionHas('import_errors');
        $this->assertDatabaseMissing('employees', ['nik' => 'EMP-NODPT-001']);
    }

    public function test_import_fails_with_unknown_bank(): void
    {
        $file = $this->createImportFile([
            ['EMP-NOBNK-001', 'No Bank', '', '', '', '2024-01-01', 'active', '', 'TK/0', '', '', '', 'FakeBank', '1234567', 'Name'],
        ]);

        $response = $this->actingAs($this->admin)->post(route('hr.employees.import'), ['file' => $file]);

        $response->assertSessionHas('import_errors');
    }

    public function test_import_rolls_back_on_validation_errors(): void
    {
        // Row 1 is valid, Row 2 has invalid PTKP → all should rollback
        $file = $this->createImportFile([
            ['ROLLBACK-001', 'Valid', '', '', '', '2024-01-01', 'active', '', 'TK/0', '', '', '', '', '', ''],
            ['ROLLBACK-002', 'Invalid', '', '', '', '2024-01-01', 'active', '', 'INVALID', '', '', '', '', '', ''],
        ]);

        $response = $this->actingAs($this->admin)->post(route('hr.employees.import'), ['file' => $file]);

        $response->assertSessionHas('import_errors');
        // Both should not exist in DB because validation is done pre-transaction
        $this->assertDatabaseMissing('employees', ['nik' => 'ROLLBACK-001']);
        $this->assertDatabaseMissing('employees', ['nik' => 'ROLLBACK-002']);
    }

    public function test_import_uses_dynamic_nik_length_settings(): void
    {
        Setting::set('nik_min_length', 10);
        Setting::set('nik_max_length', 10);

        $file = $this->createImportFile([
            ['SHORT', 'Too Short NIK', '', '', '', '2024-01-01', 'active', '', 'TK/0', '', '', '', '', '', ''],
        ]);

        $response = $this->actingAs($this->admin)->post(route('hr.employees.import'), ['file' => $file]);

        $response->assertSessionHas('import_errors');
    }

    public function test_import_rejects_empty_file(): void
    {
        $file = $this->createImportFile([]);

        $response = $this->actingAs($this->admin)->post(route('hr.employees.import'), ['file' => $file]);

        $response->assertSessionHas('import_errors');
    }

    // ═══════════════════════════════════════════════════════════════
    // PERMISSION
    // ═══════════════════════════════════════════════════════════════

    public function test_staff_without_create_permission_cannot_import(): void
    {
        $staff = User::factory()->create([
            'role'        => User::ROLE_STAFF,
            'status'      => User::STATUS_ACTIVE,
            'permissions' => ['hr.view'],
        ]);

        $file = $this->createImportFile([
            ['EMP-PERM-001', 'No Permission', '', '', '', '2024-01-01', 'active', '', 'TK/0', '', '', '', '', '', ''],
        ]);

        $response = $this->actingAs($staff)->post(route('hr.employees.import'), ['file' => $file]);

        $response->assertForbidden();
    }

    public function test_import_rejects_invalid_file_type(): void
    {
        $file = UploadedFile::fake()->create('employees.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin)->post(route('hr.employees.import'), [
            'file' => $file,
        ]);

        $response->assertSessionHasErrors('file');
    }

    // ═══════════════════════════════════════════════════════════════
    // I18N KEYS
    // ═══════════════════════════════════════════════════════════════

    public function test_i18n_import_keys_exist_for_all_languages(): void
    {
        $requiredKeys = [
            'import_excel', 'import_employees', 'import_instruction',
            'download_template', 'select_file', 'import_file_hint',
            'start_import', 'import_success', 'import_failed',
            'import_error_summary', 'import_row', 'import_empty_file',
            'import_no_valid_rows', 'import_nik_exists',
            'import_nik_duplicate_in_file', 'import_department_not_found',
            'import_position_not_found', 'import_shift_not_found',
            'import_bank_not_found',
        ];

        foreach (['en', 'id', 'ko', 'zh'] as $locale) {
            $messages = require base_path("lang/{$locale}/messages.php");
            foreach ($requiredKeys as $key) {
                $this->assertArrayHasKey($key, $messages, "Missing i18n key '{$key}' in {$locale}/messages.php");
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPER
    // ═══════════════════════════════════════════════════════════════

    private function createImportFile(array $dataRows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header row
        $headers = ['NIK', 'Nama', 'Jabatan', 'Departemen', 'Shift', 'Tanggal Bergabung', 'Status', 'NPWP', 'PTKP', 'Kategori TER', 'No. BPJS TK', 'No. BPJS Kesehatan', 'Nama Bank', 'Nomor Rekening', 'Nama Akun'];
        $sheet->fromArray([$headers], null, 'A1');

        // Data rows
        foreach ($dataRows as $i => $row) {
            $sheet->fromArray([$row], null, 'A' . ($i + 2));
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'imp_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tmpFile);

        return new UploadedFile($tmpFile, 'import_karyawan.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
