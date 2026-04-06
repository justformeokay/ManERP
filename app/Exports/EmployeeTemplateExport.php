<?php

namespace App\Exports;

use App\Models\Bank;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Setting;
use App\Models\Shift;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EmployeeTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Template'     => new Sheets\EmployeeDataSheet(),
            'Instructions' => new Sheets\EmployeeInstructionSheet(),
        ];
    }
}
