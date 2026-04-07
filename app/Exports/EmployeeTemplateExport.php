<?php

namespace App\Exports;

use App\Exports\Sheets\EmployeeDataSheet;
use App\Exports\Sheets\EmployeeInstructionSheet;
use App\Exports\Sheets\EmployeeListsSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeWriting;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeeTemplateExport implements WithMultipleSheets, WithEvents
{
    private EmployeeListsSheet $listsSheet;

    public function __construct()
    {
        $this->listsSheet = new EmployeeListsSheet();
    }

    public function sheets(): array
    {
        return [
            'Template'     => new EmployeeDataSheet(),
            'Instructions' => new EmployeeInstructionSheet(),
            'Lists'        => $this->listsSheet,
        ];
    }

    public function registerEvents(): array
    {
        return [
            BeforeWriting::class => function (BeforeWriting $event) {
                $spreadsheet = $event->writer->getDelegate();

                // ── Hide the Lists sheet ────────────────────────
                $listsSheet = $spreadsheet->getSheetByName('Lists');
                if ($listsSheet) {
                    $listsSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
                }

                // ── Finalize dropdown formulas on Template sheet ──
                $templateSheet = $spreadsheet->getSheetByName('Template');
                if (!$templateSheet) {
                    return;
                }

                // Map: placeholder tag → Lists column letter + count
                $formulaMap = [
                    'positions'   => ['col' => 'A', 'count' => $this->listsSheet->getPositionCount()],
                    'departments' => ['col' => 'B', 'count' => $this->listsSheet->getDepartmentCount()],
                    'shifts'      => ['col' => 'C', 'count' => $this->listsSheet->getShiftCount()],
                    'banks'       => ['col' => 'D', 'count' => $this->listsSheet->getBankCount()],
                    'statuses'    => ['col' => 'E', 'count' => $this->listsSheet->getStatusCount()],
                    'ptkp'        => ['col' => 'F', 'count' => $this->listsSheet->getPtkpCount()],
                    'ter'         => ['col' => 'G', 'count' => $this->listsSheet->getTerCount()],
                ];

                $maxRow = EmployeeDataSheet::MAX_ROWS + 1;

                // Column mapping: dropdown placeholder tag → template column letter
                $columnTags = [
                    'C' => 'positions',
                    'D' => 'departments',
                    'E' => 'shifts',
                    'G' => 'statuses',
                    'I' => 'ptkp',
                    'J' => 'ter',
                    'M' => 'banks',
                ];

                foreach ($columnTags as $templateCol => $tag) {
                    $info = $formulaMap[$tag];
                    if ($info['count'] === 0) {
                        continue; // No data — skip validation
                    }
                    $lastListRow = $info['count'] + 1; // +1 for header
                    $formula = "Lists!\${$info['col']}\$2:\${$info['col']}\${$lastListRow}";

                    for ($row = 2; $row <= $maxRow; $row++) {
                        $cell = $templateSheet->getCell("{$templateCol}{$row}");
                        $validation = $cell->getDataValidation();
                        if ($validation->getType() === DataValidation::TYPE_LIST) {
                            $validation->setFormula1($formula);
                        }
                    }
                }

                // Ensure Template is the active sheet when opened
                $spreadsheet->setActiveSheetIndexByName('Template');
            },
        ];
    }
}
