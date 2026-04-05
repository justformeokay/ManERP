<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AttendanceSyncController extends Controller
{
    /**
     * POST /api/v1/attendance/sync
     *
     * Bulk-import attendance records from fingerprint machines / mobile apps.
     * Uses upsert to handle duplicates gracefully (employee_id + date unique).
     */
    public function sync(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'records'                => ['required', 'array', 'min:1', 'max:500'],
            'records.*.employee_id'  => ['required', 'integer', 'exists:employees,id'],
            'records.*.date'         => ['required', 'date_format:Y-m-d'],
            'records.*.clock_in'     => ['nullable', 'date_format:Y-m-d H:i:s'],
            'records.*.clock_out'    => ['nullable', 'date_format:Y-m-d H:i:s'],
            'records.*.latitude'     => ['nullable', 'numeric', 'between:-90,90'],
            'records.*.longitude'    => ['nullable', 'numeric', 'between:-180,180'],
            'records.*.status'       => ['nullable', 'in:' . implode(',', Attendance::STATUS_OPTIONS)],
            'records.*.overtime_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'records.*.notes'        => ['nullable', 'string', 'max:500'],
            'source'                 => ['nullable', 'string', 'max:30'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => __('messages.attendance_sync_validation_failed'),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $source  = $request->input('source', 'api');
        $records = $request->input('records');
        $synced  = 0;
        $failed  = 0;

        DB::transaction(function () use ($records, $source, &$synced, &$failed) {
            foreach ($records as $record) {
                // Determine status: default 'present' if clock_in provided
                $status = $record['status'] ?? ($record['clock_in'] ? 'present' : 'absent');

                try {
                    $existing = Attendance::where('employee_id', $record['employee_id'])
                        ->whereDate('date', $record['date'])
                        ->first();

                    $data = [
                        'clock_in'       => $record['clock_in'] ?? null,
                        'clock_out'      => $record['clock_out'] ?? null,
                        'latitude'       => $record['latitude'] ?? null,
                        'longitude'      => $record['longitude'] ?? null,
                        'status'         => $status,
                        'overtime_hours' => $record['overtime_hours'] ?? 0,
                        'notes'          => $record['notes'] ?? null,
                        'source'         => $source,
                    ];

                    if ($existing) {
                        $existing->update($data);
                    } else {
                        Attendance::create(array_merge($data, [
                            'employee_id' => $record['employee_id'],
                            'date'        => $record['date'],
                        ]));
                    }
                    $synced++;
                } catch (\Throwable $e) {
                    $failed++;
                }
            }
        });

        return response()->json([
            'message' => __('messages.attendance_sync_success'),
            'synced'  => $synced,
            'failed'  => $failed,
        ]);
    }
}
