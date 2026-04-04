<?php

return [
    'system_maintenance' => 'System Maintenance',
    'description' => 'Monitor backups, disk usage, and manage log archiving.',

    // Disk Usage
    'disk_usage' => 'Disk Usage',
    'used' => 'Used',
    'free' => 'Free',
    'total' => 'Total',
    'capacity_used' => 'Capacity Used',
    'disk_warning' => 'Disk usage is above 90%. Consider cleaning up old backups or expanding storage.',
    'total_backup_size' => 'Total Backup Size',

    // Actions
    'actions' => 'Quick Actions',
    'run_full_backup' => 'Full Backup',
    'full_backup_desc' => 'Database + storage files',
    'run_db_backup' => 'Database Only',
    'db_backup_desc' => 'Quick database-only backup',
    'run_log_archive' => 'Archive Logs',
    'log_archive_desc' => 'Archive logs older than 1 year',

    // Backups Table
    'recent_backups' => 'Recent Backups',
    'files' => 'files',
    'no_backups' => 'No backups found.',
    'no_backups_hint' => 'Run your first backup using the button above or wait for the scheduled backup.',
    'filename' => 'Filename',
    'size' => 'Size',
    'created' => 'Created',
    'action' => 'Action',
    'latest' => 'Latest',
    'download' => 'Download',

    // Log Stats
    'active_logs' => 'Active Logs',
    'archived_logs' => 'Archived Logs',
    'records_in_activity_logs' => 'Records in activity_logs table',
    'records_in_archive' => 'Records in archive table',

    // Schedule
    'schedule' => 'Backup Schedule',
    'full_backup' => 'Full Backup',
    'db_backup' => 'DB Backup',
    'cleanup' => 'Cleanup',
    'log_archive' => 'Log Archive',
    'daily_at' => 'Daily at :time',
    'every_6h' => 'Every 6 hours',
    'weekly_sunday' => 'Weekly (Sunday)',
    'retention_daily' => 'Daily backups kept for :days days',
    'retention_weekly' => 'Weekly backups kept for :weeks weeks',
    'retention_monthly' => 'Monthly backups kept for :months months',

    // Flash Messages
    'backup_not_found' => 'Backup file not found.',
    'backup_started' => ':type backup has been started successfully.',
    'backup_failed' => 'Backup failed: :error',
    'archive_complete' => 'Log archiving completed.',
    'archive_failed' => 'Log archiving failed: :error',
];
