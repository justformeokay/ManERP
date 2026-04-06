<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Shift;
use Carbon\Carbon;

class AttendanceService
{
    /**
     * Process a check-in: resolve shift, detect lateness, calculate late_minutes.
     *
     * @param Employee $employee
     * @param string   $date     Y-m-d
     * @param string|null $clockIn  Y-m-d H:i:s
     * @param array    $extra    Optional: clock_out, latitude, longitude, notes, source, overtime_hours
     * @return Attendance
     */
    public function processCheckIn(
        Employee $employee,
        string $date,
        ?string $clockIn,
        array $extra = []
    ): Attendance {
        $shift = $employee->getShiftForDate($date);

        // Determine status & late_minutes from shift
        $status      = 'present';
        $lateMinutes = 0;
        $shiftId     = $shift?->id;

        if ($clockIn && $shift) {
            [$status, $lateMinutes] = $this->evaluateLateness($clockIn, $shift);
        } elseif (!$clockIn) {
            $status = 'absent';
        }

        // Allow explicit status override (e.g. 'leave', 'half_day')
        if (isset($extra['status']) && in_array($extra['status'], Attendance::STATUS_OPTIONS, true)) {
            $status = $extra['status'];
            // Only keep late_minutes if status is 'late'
            if ($status !== 'late') {
                $lateMinutes = 0;
            }
        }

        // Overtime calculation if clock_out provided
        $overtimeHours = (float) ($extra['overtime_hours'] ?? 0);
        if (isset($extra['clock_out']) && $shift && $overtimeHours == 0) {
            $overtimeHours = $this->calculateOvertime($extra['clock_out'], $shift, $date);
        }

        $data = [
            'clock_in'       => $clockIn,
            'clock_out'      => $extra['clock_out'] ?? null,
            'latitude'       => $extra['latitude'] ?? null,
            'longitude'      => $extra['longitude'] ?? null,
            'status'         => $status,
            'late_minutes'   => $lateMinutes,
            'overtime_hours' => $overtimeHours,
            'notes'          => $extra['notes'] ?? null,
            'source'         => $extra['source'] ?? 'system',
            'shift_id'       => $shiftId,
        ];

        // Upsert: update existing or create new
        $existing = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $date)
            ->first();

        if ($existing) {
            $existing->update($data);
            return $existing->fresh();
        }

        return Attendance::create(array_merge($data, [
            'employee_id' => $employee->id,
            'date'        => $date,
        ]));
    }

    /**
     * Process a check-out update: recalculate overtime.
     */
    public function processCheckOut(Attendance $attendance, string $clockOut): Attendance
    {
        $shift         = $attendance->shift;
        $overtimeHours = 0;

        if ($shift) {
            $overtimeHours = $this->calculateOvertime($clockOut, $shift, $attendance->date->format('Y-m-d'));
        }

        $attendance->update([
            'clock_out'      => $clockOut,
            'overtime_hours' => $overtimeHours,
        ]);

        return $attendance->fresh();
    }

    /**
     * Evaluate lateness: compare clock_in against shift start_time + grace_period.
     *
     * @return array{0: string, 1: int} [status, late_minutes]
     */
    public function evaluateLateness(string $clockIn, Shift $shift): array
    {
        $clockInTime  = Carbon::parse($clockIn);
        $shiftDate    = $clockInTime->format('Y-m-d');

        // For cross-day shifts (e.g. 22:00–06:00), if clock_in is before noon,
        // assume the shift started the previous day
        $shiftStart = Carbon::parse($shiftDate . ' ' . $shift->start_time);
        if ($shift->isCrossDay() && $clockInTime->format('H:i:s') < '12:00:00') {
            $shiftStart = $shiftStart->subDay();
        }

        $graceCutoff = $shiftStart->copy()->addMinutes($shift->grace_period);

        if ($clockInTime->gt($graceCutoff)) {
            $lateMinutes = (int) abs($clockInTime->diffInMinutes($shiftStart));
            return ['late', max($lateMinutes, 1)];
        }

        return ['present', 0];
    }

    /**
     * Calculate overtime hours when clock_out exceeds shift end_time.
     * Handles cross-day shifts (22:00–06:00).
     */
    public function calculateOvertime(string $clockOut, Shift $shift, string $date): float
    {
        $clockOutTime = Carbon::parse($clockOut);
        $shiftEnd     = Carbon::parse($date . ' ' . $shift->end_time);

        // Cross-day shift: end_time is on the next day
        if ($shift->isCrossDay()) {
            $shiftEnd = $shiftEnd->addDay();
        }

        if ($clockOutTime->gt($shiftEnd)) {
            $overtimeMinutes = abs($clockOutTime->diffInMinutes($shiftEnd));
            return round($overtimeMinutes / 60, 2);
        }

        return 0;
    }
}
