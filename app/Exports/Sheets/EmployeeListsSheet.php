<?php

namespace App\Exports\Sheets;

use App\Models\Bank;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Shift;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Hidden reference sheet providing data for dropdown validations.
 * Columns: A=Positions, B=Departments, C=Shifts, D=Banks, E=Status, F=PTKP, G=TER
 */
class EmployeeListsSheet implements FromArray, WithStyles, WithTitle
{
    private array $positions;
    private array $departments;
    private array $shifts;
    private array $banks;
    private array $statuses;
    private array $ptkpOptions;
    private array $terCategories;

    public function __construct()
    {
        $this->positions    = Position::active()->orderBy('name')->get()->map(fn ($p) => "[{$p->code}] - {$p->name}")->values()->all();
        $this->departments  = Department::active()->orderBy('name')->get()->map(fn ($d) => "[{$d->code}] - {$d->name}")->values()->all();
        $this->shifts       = Shift::active()->orderBy('name')->pluck('name')->all();
        $this->banks        = Bank::active()->orderBy('name')->pluck('name')->all();
        $this->statuses     = Employee::statusOptions();
        $this->ptkpOptions  = Employee::ptkpOptions();
        $this->terCategories = Employee::TER_CATEGORIES;
    }

    public function title(): string
    {
        return 'Lists';
    }

    public function array(): array
    {
        // Build rows: each column is a different list, padded to the longest list
        $columns = [
            $this->positions,
            $this->departments,
            $this->shifts,
            $this->banks,
            $this->statuses,
            $this->ptkpOptions,
            $this->terCategories,
        ];

        $maxRows = max(array_map('count', $columns));
        $rows = [];

        // Header row
        $rows[] = ['Jabatan', 'Departemen', 'Shift', 'Bank', 'Status', 'PTKP', 'TER'];

        for ($i = 0; $i < $maxRows; $i++) {
            $rows[] = [
                $columns[0][$i] ?? '',
                $columns[1][$i] ?? '',
                $columns[2][$i] ?? '',
                $columns[3][$i] ?? '',
                $columns[4][$i] ?? '',
                $columns[5][$i] ?? '',
                $columns[6][$i] ?? '',
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        // Header bold
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true],
        ]);

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }

    // ── Accessors for EmployeeTemplateExport to build named ranges ──

    public function getPositionCount(): int
    {
        return count($this->positions);
    }

    public function getDepartmentCount(): int
    {
        return count($this->departments);
    }

    public function getShiftCount(): int
    {
        return count($this->shifts);
    }

    public function getBankCount(): int
    {
        return count($this->banks);
    }

    public function getStatusCount(): int
    {
        return count($this->statuses);
    }

    public function getPtkpCount(): int
    {
        return count($this->ptkpOptions);
    }

    public function getTerCount(): int
    {
        return count($this->terCategories);
    }
}
