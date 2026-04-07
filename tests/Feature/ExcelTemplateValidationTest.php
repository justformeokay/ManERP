<?php

namespace Tests\Feature;

use App\Exports\EmployeeTemplateExport;
use App\Exports\Sheets\EmployeeDataSheet;
use App\Models\Bank;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\Rules\Password;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ExcelTemplateValidationTest extends TestCase
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

        // Seed standard data for dropdown validation
        Department::create(['name' => 'Produksi', 'code' => 'PROD', 'is_active' => true]);
        Department::create(['name' => 'Finance', 'code' => 'FINA', 'is_active' => true]);
        Position::create(['name' => 'Manager', 'code' => 'MGR', 'is_active' => true]);
        Position::create(['name' => 'Staff', 'code' => 'STF', 'is_active' => true]);
        Shift::create([
            'name' => 'Shift Pagi', 'start_time' => '08:00', 'end_time' => '17:00',
            'grace_period' => 15, 'is_night_shift' => false, 'night_shift_bonus' => 0, 'is_active' => true,
        ]);
        Bank::create(['name' => 'BCA', 'code' => 'BCA', 'is_active' => true]);
        Bank::create(['name' => 'BNI', 'code' => 'BNI', 'is_active' => true]);
    }

    private function downloadAndLoadTemplate(): Spreadsheet
    {
        $response = $this->actingAs($this->admin)->get(route('hr.employees.template'));
        $response->assertOk();
        $tmpFile = $response->baseResponse->getFile()->getPathname();
        return IOFactory::load($tmpFile);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 1: HIDDEN LISTS SHEET
    // ═══════════════════════════════════════════════════════════════

    public function test_template_has_three_sheets(): void
    {
        $spreadsheet = $this->downloadAndLoadTemplate();

        $this->assertNotNull($spreadsheet->getSheetByName('Template'));
        $this->assertNotNull($spreadsheet->getSheetByName('Instructions'));
        $this->assertNotNull($spreadsheet->getSheetByName('Lists'));
    }

    public function test_lists_sheet_is_hidden(): void
    {
        $spreadsheet = $this->downloadAndLoadTemplate();
        $listsSheet = $spreadsheet->getSheetByName('Lists');

        $this->assertNotNull($listsSheet);
        $this->assertEquals(
            \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN,
            $listsSheet->getSheetState()
        );
    }

    public function test_lists_sheet_contains_positions_with_code_format(): void
    {
        $spreadsheet = $this->downloadAndLoadTemplate();
        $listsSheet = $spreadsheet->getSheetByName('Lists');

        // Column A: Positions
        $this->assertEquals('Jabatan', $listsSheet->getCell('A1')->getValue());
        $posValues = [];
        for ($row = 2; $row <= 10; $row++) {
            $val = $listsSheet->getCell("A{$row}")->getValue();
            if ($val) $posValues[] = $val;
        }
        $this->assertContains('[MGR] - Manager', $posValues);
        $this->assertContains('[STF] - Staff', $posValues);
    }

    public function test_lists_sheet_contains_departments_with_code_format(): void
    {
        $spreadsheet = $this->downloadAndLoadTemplate();
        $listsSheet = $spreadsheet->getSheetByName('Lists');

        // Column B: Departments
        $this->assertEquals('Departemen', $listsSheet->getCell('B1')->getValue());
        $deptValues = [];
        for ($row = 2; $row <= 10; $row++) {
            $val = $listsSheet->getCell("B{$row}")->getValue();
            if ($val) $deptValues[] = $val;
        }
        $this->assertContains('[PROD] - Produksi', $deptValues);
        $this->assertContains('[FINA] - Finance', $deptValues);
    }

    public function test_lists_sheet_contains_shifts(): void
    {
        $spreadsheet = $this->downloadAndLoadTemplate();
        $listsSheet = $spreadsheet->getSheetByName('Lists');

        $this->assertEquals('Shift', $listsSheet->getCell('C1')->getValue());
        $this->assertEquals('Shift Pagi', $listsSheet->getCell('C2')->getValue());
    }

    public function test_lists_sheet_contains_banks(): void
    {
        $spreadsheet = $this->downloadAndLoadTemplate();
        $listsSheet = $spreadsheet->getSheetByName('Lists');

        $this->assertEquals('Bank', $listsSheet->getCell('D1')->getValue());
        $bankValues = [];
        for ($row = 2; $row <= 10; $row++) {
            $val = $listsSheet->getCell("D{$row}")->getValue();
            if ($val) $bankValues[] = $val;
        }
        $this->assertContains('BCA', $bankValues);
        $this->assertContains('BNI', $bankValues);
    }

    public function test_lists_sheet_contains_status_ptkp_ter(): void
    {
        $spreadsheet = $this->downloadAndLoadTemplate();
        $listsSheet = $spreadsheet->getSheetByName('Lists');

        // Column E: Status
        $this->assertEquals('Status', $listsSheet->getCell('E1')->getValue());
        $this->assertEquals('active', $listsSheet->getCell('E2')->getValue());

        // Column F: PTKP
        $this->assertEquals('PTKP', $listsSheet->getCell('F1')->getValue());
        $this->assertEquals('TK/0', $listsSheet->getCell('F2')->getValue());

        // Column G: TER
        $this->assertEquals('TER', $listsSheet->getCell('G1')->getValue());
        $this->assertEquals('A', $listsSheet->getCell('G2')->getValue());
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 1: DROPDOWN DATA VALIDATION
    // ═══════════════════════════════════════════════════════════════

    public function test_position_column_has_list_validation(): void
    {
        $spreadsheet = $this->downloadAndLoadTemplate();
        $sheet = $spreadsheet->getSheetByName('Template');

        $validation = $sheet->getCell('C2')->getDataValidation();
        $this->assertEquals(DataValidation::TYPE_LIST, $validation->getType());
        $this->assertStringContainsString('Lists!', $validation->getFormula1());
        $this->assertStringContainsString('$A$', $validation->getFormula1());
    }

    public function test_department_column_has_list_validation(): void
    {
        $spreadsheet = $this->downloadAndLoadTemplate();
        $sheet = $spreadsheet->getSheetByName('Template');

        $validation = $sheet->getCell('D2')->getDataValidation();
        $this->assertEquals(DataValidation::TYPE_LIST, $validation->getType());
        $this->assertStringContainsString('Lists!', $validation->getFormula1());
        $this->assertStringContainsString('$B$', $validation->getFormula1());
    }

    public function test_shift_column_has_list_validation(): void
    {
        $spreadsheet = $this->downloadAndLoadTemplate();
        $sheet = $spreadsheet->getSheetByName('Template');

        $validation = $sheet->getCell('E2')->getDataValidation();
        $this->assertEquals(DataValidation::TYPE_LIST, $validation->getType());
        $this->assertStringContainsString('Lists!', $validation->getFormula1());
    }

    public function test_status_column_has_list_validation(): void
    {
        $spreadsheet = $this->downloadAndLoadTemplate();
        $sheet = $spreadsheet->getSheetByName('Template');

        $validation = $sheet->getCell('G2')->getDataValidation();
        $this->assertEquals(DataValidation::TYPE_LIST, $validation->getType());
    }

    public function test_ptkp_column_has_list_validation(): void
    {
        $spreadsheet = $this->downloadAndLoadTemplate();
        $sheet = $spreadsheet->getSheetByName('Template');

        $validation = $sheet->getCell('I2')->getDataValidation();
        $this->assertEquals(DataValidation::TYPE_LIST, $validation->getType());
    }

    public function test_ter_column_has_list_validation(): void
    {
        $spreadsheet = $this->downloadAndLoadTemplate();
        $sheet = $spreadsheet->getSheetByName('Template');

        $validation = $sheet->getCell('J2')->getDataValidation();
        $this->assertEquals(DataValidation::TYPE_LIST, $validation->getType());
    }

    public function test_bank_column_has_list_validation(): void
    {
        $spreadsheet = $this->downloadAndLoadTemplate();
        $sheet = $spreadsheet->getSheetByName('Template');

        $validation = $sheet->getCell('M2')->getDataValidation();
        $this->assertEquals(DataValidation::TYPE_LIST, $validation->getType());
        $this->assertStringContainsString('Lists!', $validation->getFormula1());
        $this->assertStringContainsString('$D$', $validation->getFormula1());
    }

    public function test_dropdown_validation_covers_500_rows(): void
    {
        $spreadsheet = $this->downloadAndLoadTemplate();
        $sheet = $spreadsheet->getSheetByName('Template');

        // Row 501 (500 data rows + 1 header) should have validation
        $validation501 = $sheet->getCell('C501')->getDataValidation();
        $this->assertEquals(DataValidation::TYPE_LIST, $validation501->getType());

        // Row 502 should NOT have validation
        $validation502 = $sheet->getCell('C502')->getDataValidation();
        $this->assertNotEquals(DataValidation::TYPE_LIST, $validation502->getType());
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 2: DATE & FORMAT VALIDATION
    // ═══════════════════════════════════════════════════════════════

    public function test_join_date_column_has_date_validation(): void
    {
        $spreadsheet = $this->downloadAndLoadTemplate();
        $sheet = $spreadsheet->getSheetByName('Template');

        $validation = $sheet->getCell('F2')->getDataValidation();
        $this->assertEquals(DataValidation::TYPE_DATE, $validation->getType());
        $this->assertTrue($validation->getShowInputMessage());
        $this->assertStringContainsString('YYYY-MM-DD', $validation->getPrompt());
    }

    public function test_nik_column_is_text_format(): void
    {
        $spreadsheet = $this->downloadAndLoadTemplate();
        $sheet = $spreadsheet->getSheetByName('Template');

        $format = $sheet->getStyle('A2')->getNumberFormat()->getFormatCode();
        $this->assertEquals('@', $format); // @ = TEXT format
    }

    public function test_bank_account_column_is_text_format(): void
    {
        $spreadsheet = $this->downloadAndLoadTemplate();
        $sheet = $spreadsheet->getSheetByName('Template');

        $format = $sheet->getStyle('N2')->getNumberFormat()->getFormatCode();
        $this->assertEquals('@', $format);
    }

    public function test_bpjs_columns_are_text_format(): void
    {
        $spreadsheet = $this->downloadAndLoadTemplate();
        $sheet = $spreadsheet->getSheetByName('Template');

        $formatK = $sheet->getStyle('K2')->getNumberFormat()->getFormatCode();
        $formatL = $sheet->getStyle('L2')->getNumberFormat()->getFormatCode();
        $this->assertEquals('@', $formatK); // BPJS TK
        $this->assertEquals('@', $formatL); // BPJS Kesehatan
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 3: COLOR CODING
    // ═══════════════════════════════════════════════════════════════

    public function test_dropdown_columns_have_light_blue_header(): void
    {
        $spreadsheet = $this->downloadAndLoadTemplate();
        $sheet = $spreadsheet->getSheetByName('Template');

        foreach (EmployeeDataSheet::DROPDOWN_COLUMNS as $col) {
            $fillColor = $sheet->getStyle("{$col}1")->getFill()->getStartColor()->getRGB();
            $this->assertEquals('0EA5E9', $fillColor, "Column {$col} header should be light blue (sky-500)");
        }
    }

    public function test_date_column_has_amber_header(): void
    {
        $spreadsheet = $this->downloadAndLoadTemplate();
        $sheet = $spreadsheet->getSheetByName('Template');

        $fillColor = $sheet->getStyle('F1')->getFill()->getStartColor()->getRGB();
        $this->assertEquals('F59E0B', $fillColor, 'Date column header should be amber');
    }

    public function test_non_dropdown_columns_have_indigo_header(): void
    {
        $spreadsheet = $this->downloadAndLoadTemplate();
        $sheet = $spreadsheet->getSheetByName('Template');

        // Columns A, B, H, K, L, N, O should be indigo
        $indigoColumns = ['A', 'B', 'H', 'K', 'L', 'N', 'O'];
        foreach ($indigoColumns as $col) {
            $fillColor = $sheet->getStyle("{$col}1")->getFill()->getStartColor()->getRGB();
            $this->assertEquals('4F46E5', $fillColor, "Column {$col} header should be indigo");
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 3: INSTRUCTIONS UPDATES
    // ═══════════════════════════════════════════════════════════════

    public function test_instructions_mention_dropdown(): void
    {
        $spreadsheet = $this->downloadAndLoadTemplate();
        $instructions = $spreadsheet->getSheetByName('Instructions');

        // Search for "DROPDOWN" keyword in the instructions content
        $found = false;
        for ($row = 1; $row <= 35; $row++) {
            $val = (string) $instructions->getCell("C{$row}")->getValue();
            if (stripos($val, 'DROPDOWN') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Instructions should mention DROPDOWN');
    }

    public function test_instructions_mention_color_coding(): void
    {
        $spreadsheet = $this->downloadAndLoadTemplate();
        $instructions = $spreadsheet->getSheetByName('Instructions');

        $found = false;
        for ($row = 1; $row <= 35; $row++) {
            $val = (string) $instructions->getCell("A{$row}")->getValue();
            if (stripos($val, 'Biru Muda') !== false || stripos($val, 'dropdown') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Instructions should explain color coding');
    }

    public function test_instructions_mention_500_row_limit(): void
    {
        $spreadsheet = $this->downloadAndLoadTemplate();
        $instructions = $spreadsheet->getSheetByName('Instructions');

        $found = false;
        for ($row = 1; $row <= 35; $row++) {
            $val = (string) $instructions->getCell("A{$row}")->getValue();
            if (stripos($val, '500') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Instructions should mention 500 row limit');
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 4: IMPORT COMPATIBILITY WITH [CODE] FORMAT
    // ═══════════════════════════════════════════════════════════════

    public function test_import_accepts_code_name_format_for_position(): void
    {
        $file = $this->createImportFile([
            ['EMP-FMT-001', 'Test User', '[STF] - Staff', '', '', '2024-01-01', 'active', '', 'TK/0', '', '', '', '', '', ''],
        ]);

        $this->actingAs($this->admin)->post(route('hr.employees.import'), ['file' => $file]);

        $emp = Employee::where('nik', 'EMP-FMT-001')->first();
        $this->assertNotNull($emp);
        $this->assertEquals('Staff', $emp->position);
    }

    public function test_import_accepts_code_name_format_for_department(): void
    {
        $file = $this->createImportFile([
            ['EMP-FMT-002', 'Test User', '', '[PROD] - Produksi', '', '2024-01-01', 'active', '', 'TK/0', '', '', '', '', '', ''],
        ]);

        $this->actingAs($this->admin)->post(route('hr.employees.import'), ['file' => $file]);

        $emp = Employee::where('nik', 'EMP-FMT-002')->first();
        $this->assertNotNull($emp);
        $this->assertEquals('Produksi', $emp->department);
    }

    public function test_import_still_accepts_plain_name_format(): void
    {
        $file = $this->createImportFile([
            ['EMP-FMT-003', 'Test User', 'Manager', 'Finance', '', '2024-01-01', 'active', '', 'TK/0', '', '', '', '', '', ''],
        ]);

        $this->actingAs($this->admin)->post(route('hr.employees.import'), ['file' => $file]);

        $emp = Employee::where('nik', 'EMP-FMT-003')->first();
        $this->assertNotNull($emp);
        $this->assertEquals('Manager', $emp->position);
        $this->assertEquals('Finance', $emp->department);
    }

    // ═══════════════════════════════════════════════════════════════
    // INACTIVE DATA EXCLUDED FROM DROPDOWNS
    // ═══════════════════════════════════════════════════════════════

    public function test_inactive_department_not_in_lists_sheet(): void
    {
        Department::create(['name' => 'Closed Dept', 'code' => 'CLD', 'is_active' => false]);

        $spreadsheet = $this->downloadAndLoadTemplate();
        $listsSheet = $spreadsheet->getSheetByName('Lists');

        $deptValues = [];
        for ($row = 2; $row <= 20; $row++) {
            $val = $listsSheet->getCell("B{$row}")->getValue();
            if ($val) $deptValues[] = $val;
        }
        $this->assertNotContains('[CLD] - Closed Dept', $deptValues);
    }

    public function test_inactive_position_not_in_lists_sheet(): void
    {
        Position::create(['name' => 'Retired Pos', 'code' => 'RTP', 'is_active' => false]);

        $spreadsheet = $this->downloadAndLoadTemplate();
        $listsSheet = $spreadsheet->getSheetByName('Lists');

        $posValues = [];
        for ($row = 2; $row <= 20; $row++) {
            $val = $listsSheet->getCell("A{$row}")->getValue();
            if ($val) $posValues[] = $val;
        }
        $this->assertNotContains('[RTP] - Retired Pos', $posValues);
    }

    // ═══════════════════════════════════════════════════════════════
    // TEMPLATE IS ACTIVE SHEET
    // ═══════════════════════════════════════════════════════════════

    public function test_template_is_active_sheet_when_opened(): void
    {
        $spreadsheet = $this->downloadAndLoadTemplate();
        $activeSheet = $spreadsheet->getActiveSheet();
        $this->assertEquals('Template', $activeSheet->getTitle());
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPER
    // ═══════════════════════════════════════════════════════════════

    private function createImportFile(array $dataRows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['NIK', 'Nama', 'Jabatan', 'Departemen', 'Shift', 'Tanggal Bergabung', 'Status', 'NPWP', 'PTKP', 'Kategori TER', 'No. BPJS TK', 'No. BPJS Kesehatan', 'Nama Bank', 'Nomor Rekening', 'Nama Akun'];
        $sheet->fromArray([$headers], null, 'A1');

        foreach ($dataRows as $i => $row) {
            $sheet->fromArray([$row], null, 'A' . ($i + 2));
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'imp_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($tmpFile);

        return new UploadedFile($tmpFile, 'import_karyawan.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
