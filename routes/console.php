<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Automated Backups ────────────────────────────────────────────────
// Full backup (DB + storage files): daily at 02:00
Schedule::command('backup:run')->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/backup.log'));

// Database-only backup: every 6 hours
Schedule::command('backup:run --only-db')->everySixHours()
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/backup.log'));

// Cleanup old backups per retention policy: daily at 03:00
Schedule::command('backup:clean')->dailyAt('03:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/backup.log'));

// Health monitor: daily at 06:00 — fires notification if backup is stale or disk full
Schedule::command('backup:monitor')->dailyAt('06:00')
    ->appendOutputTo(storage_path('logs/backup.log'));

// ── Log Archiving ────────────────────────────────────────────────────
// Archive activity_logs older than 1 year: weekly on Sunday at 04:00
Schedule::command('log:archive')->weeklyOn(0, '04:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/log-archive.log'));

// ── Attendance: Auto-mark absent ──────────────────────────────────────
// Runs at midnight to mark employees who did not check in yesterday as Absent
Schedule::command('attendance:mark-absent')->dailyAt('00:05')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/attendance.log'));

// ── CRM: Lead Follow-up Reminders ────────────────────────────────────
// Sends in-app reminders for stale leads (idle > grace period) daily at 08:00
Schedule::command('leads:send-reminders')->dailyAt('08:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/lead-reminders.log'));
