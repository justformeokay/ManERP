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

        // Skip weekends
        if ($date->isWeekend()) {
            $this->info("Skipped — {$date->toDateString()} is a weekend.");
            return self::SUCCESS;
        }

        $activeEmployeeIds = Employee::active()->pluck('id');

        // Employees who already have an attendance record for this date
        $presentIds = Attendance::whereDate('date', $date->toDateString())
            ->pluck('employee_id');

        $absentIds = $activeEmployeeIds->diff($presentIds);

        if ($absentIds->isEmpty()) {
            $this->info("No absent employees on {$date->toDateString()}.");
            return self::SUCCESS;
        }

        $records = $absentIds->map(fn ($id) => [
            'employee_id' => $id,
            'date'        => $date->toDateString(),
            'status'      => 'absent',
            'source'      => 'system',
            'notes'       => 'Auto-marked absent by system',
            'created_at'  => now(),
            'updated_at'  => now(),
        ])->values()->all();

        Attendance::insertOrIgnore($records);

        $count = count($records);
        $this->info("Marked {$count} employee(s) as absent on {$date->toDateString()}.");

        return self::SUCCESS;
    }
}
