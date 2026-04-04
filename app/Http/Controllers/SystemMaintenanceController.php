<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SystemMaintenanceController extends Controller
{
    /**
     * System Maintenance dashboard — disk usage, recent backups, archive stats.
     */
    public function index()
    {
        $disk = Storage::disk('backups');

        // Disk capacity info
        $storagePath = storage_path();
        $diskFreeBytes  = @disk_free_space($storagePath) ?: 0;
        $diskTotalBytes = @disk_total_space($storagePath) ?: 1;
        $diskUsedBytes  = $diskTotalBytes - $diskFreeBytes;
        $diskUsagePercent = round(($diskUsedBytes / $diskTotalBytes) * 100, 1);

        // List recent backup files (last 5)
        $backupDir = config('app.name', 'ManERP');
        $allFiles = $disk->allFiles($backupDir);

        // Sort by last modified descending and take 5
        $filesMeta = collect($allFiles)
            ->filter(fn($f) => str_ends_with($f, '.zip'))
            ->map(fn($f) => [
                'path'      => $f,
                'name'      => basename($f),
                'size'      => $disk->size($f),
                'modified'  => $disk->lastModified($f),
            ])
            ->sortByDesc('modified')
            ->take(5)
            ->values();

        // Total backup size
        $totalBackupSize = collect($allFiles)
            ->filter(fn($f) => str_ends_with($f, '.zip'))
            ->sum(fn($f) => $disk->size($f));

        // Archive stats
        $archiveCount = DB::table('activity_logs_archives')->count();
        $mainLogCount = DB::table('activity_logs')->count();

        return view('admin.system-maintenance', compact(
            'diskFreeBytes',
            'diskTotalBytes',
            'diskUsedBytes',
            'diskUsagePercent',
            'filesMeta',
            'totalBackupSize',
            'archiveCount',
            'mainLogCount',
        ));
    }

    /**
     * Download a specific backup file (admin only).
     */
    public function downloadBackup(Request $request)
    {
        $filename = $request->query('file');
        $disk = Storage::disk('backups');

        if (!$filename || !$disk->exists($filename)) {
            return back()->with('error', __('maintenance.backup_not_found'));
        }

        return $disk->download($filename);
    }

    /**
     * Run manual full backup.
     */
    public function runBackup(Request $request)
    {
        $type = $request->input('type', 'full');

        try {
            if ($type === 'db-only') {
                Artisan::call('backup:run', ['--only-db' => true, '--disable-notifications' => true]);
            } else {
                Artisan::call('backup:run', ['--disable-notifications' => true]);
            }

            $output = Artisan::output();

            return back()->with('success', __('maintenance.backup_started', ['type' => $type]));
        } catch (\Throwable $e) {
            return back()->with('error', __('maintenance.backup_failed', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Run manual log archive.
     */
    public function runArchive(Request $request)
    {
        try {
            Artisan::call('log:archive', [
                '--months' => 12,
                '--export-csv' => true,
            ]);

            $output = Artisan::output();

            return back()->with('success', __('maintenance.archive_complete') . ' ' . $output);
        } catch (\Throwable $e) {
            return back()->with('error', __('maintenance.archive_failed', ['error' => $e->getMessage()]));
        }
    }
}
