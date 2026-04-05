<?php

use App\Http\Controllers\Api\AttendanceSyncController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Prefix: /api/v1
| Middleware: auth:sanctum (token-based)
|
*/

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::post('attendance/sync', [AttendanceSyncController::class, 'sync'])
        ->name('api.attendance.sync');
});
