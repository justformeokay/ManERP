<?php

namespace App\Console\Commands;

use App\Services\AuditLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ArchiveActivityLogs extends Command
{
    protected $signature = 'log:archive
                            {--months=12 : Archive logs older than this many months}
                            {--export-csv : Also export to CSV before archiving}
                            {--dry-run : Show what would be archived without archiving}';

    protected $description = 'Archive activity_logs older than N months to activity_logs_archives, preserving checksums';

    public function handle(): int
    {
        $months = (int) $this->option('months');
        $cutoff = now()->subMonths($months)->endOfDay()->toDateTimeString();
        $dryRun = $this->option('dry-run');

        $count = DB::table('activity_logs')
            ->where('created_at', '<', $cutoff)
            ->count();

        if ($count === 0) {
            $this->info('No activity logs older than ' . $months . ' months to archive.');
            return self::SUCCESS;
        }

        $this->info("Found {$count} log(s) older than {$months} months (before {$cutoff}).");

        if ($dryRun) {
            $this->warn('[DRY RUN] No records will be moved.');
            return self::SUCCESS;
        }

        // Optional CSV export before archiving
        if ($this->option('export-csv')) {
            $this->exportToCsv($cutoff);
        }

        // Process in chunks to avoid memory issues
        $archived = 0;
        $verified = 0;
        $chunkSize = 500;

        DB::table('activity_logs')
            ->where('created_at', '<', $cutoff)
            ->orderBy('id')
            ->chunk($chunkSize, function ($rows) use (&$archived, &$verified) {
                $inserts = [];
                foreach ($rows as $row) {
                    // Verify checksum before archiving
                    if ($row->checksum) {
                        $payload = json_encode([
                            $row->user_id,
                            $row->module,
                            $row->action,
                            $row->description,
                            $row->ip_address,
                            $row->created_at, // Already a string from DB
                        ]);
                        $valid = hash_equals(
                            $row->checksum,
                            hash_hmac('sha256', $payload, config('app.key'))
                        );
                        if ($valid) {
                            $verified++;
                        }
                    }

                    $inserts[] = [
                        'original_id'    => $row->id,
                        'user_id'        => $row->user_id,
                        'module'         => $row->module,
                        'action'         => $row->action,
                        'auditable_type' => $row->auditable_type,
                        'auditable_id'   => $row->auditable_id,
                        'description'    => $row->description,
                        'old_data'       => $row->old_data,
                        'new_data'       => $row->new_data,
                        'changes'        => $row->changes,
                        'ip_address'     => $row->ip_address,
                        'user_agent'     => $row->user_agent,
                        'session_id'     => $row->session_id,
                        'checksum'       => $row->checksum,    // Preserved exactly
                        'created_at'     => $row->created_at,  // Preserved exactly
                        'archived_at'    => now(),
                    ];
                }

                // Transaction: insert into archive, then delete from source
                DB::transaction(function () use ($inserts, $rows) {
                    DB::table('activity_logs_archives')->insert($inserts);

                    // Bypass Eloquent immutability by using query builder directly
                    DB::table('activity_logs')
                        ->whereIn('id', collect($rows)->pluck('id')->all())
                        ->delete();
                });

                $archived += count($inserts);
            });

        $this->info("Archived: {$archived} record(s).");
        $this->info("Checksum verified: {$verified} record(s) with valid HMAC.");

        return self::SUCCESS;
    }

    private function exportToCsv(string $cutoff): void
    {
        $filename = 'log-archives/activity_logs_' . now()->format('Y-m-d_His') . '.csv';
        $path = storage_path('app/' . $filename);

        // Ensure directory exists
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        $fp = fopen($path, 'w');

        // Header row
        fputcsv($fp, [
            'id', 'user_id', 'module', 'action',
            'auditable_type', 'auditable_id',
            'description', 'old_data', 'new_data', 'changes',
            'ip_address', 'user_agent', 'session_id',
            'checksum', 'created_at',
        ]);

        DB::table('activity_logs')
            ->where('created_at', '<', $cutoff)
            ->orderBy('id')
            ->chunk(500, function ($rows) use ($fp) {
                foreach ($rows as $row) {
                    fputcsv($fp, [
                        $row->id, $row->user_id, $row->module, $row->action,
                        $row->auditable_type, $row->auditable_id,
                        $row->description, $row->old_data, $row->new_data, $row->changes,
                        $row->ip_address, $row->user_agent, $row->session_id,
                        $row->checksum, $row->created_at,
                    ]);
                }
            });

        fclose($fp);
        $this->info("Exported CSV to: {$filename}");
    }
}
