<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkAbsentEmployees extends Command
{
    protected $signature = 'attendance:mark-absent {--date= : Date to process (default: yesterday)}';

    protected $description = 'Mark employees who did not check in as Absent for a given workday';

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::yesterday();

        $activeEmployees = Employee::active()->get();

        // Employees who already have an attendance record for this date
        $presentIds = Attendance::whereDate('date', $date->toDateString())
            ->pluck('employee_id');

        $records = [];

        foreach ($activeEmployees as $employee) {
            // Skip if already has attendance
            if ($presentIds->contains($employee->id)) {
                continue;
            }

            // If employee has a shift, they work on the shift schedule (including weekends).
            // If no shift, skip weekends.
            $shift = $employee->getShiftForDate($date->toDateString());
            if (!$shift && $date->isWeekend()) {
                continue;
            }

            $records[] = [
                'employee_id' => $employee->id,
                'date'        => $date->toDateString(),
                'status'      => 'absent',
                'late_minutes' => 0,
                'shift_id'    => $shift?->id,
                'source'      => 'system',
                'notes'       => 'Auto-marked absent by system',
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        }

        if (empty($records)) {
            $this->info("No absent employees on {$date->toDateString()}.");
            return self::SUCCESS;
        }

        Attendance::insertOrIgnore($records);

        $count = count($records);
        $this->info("Marked {$count} employee(s) as absent on {$date->toDateString()}.");

        return self::SUCCESS;
    }
}
