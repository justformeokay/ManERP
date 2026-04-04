<?php

return [
    'system_maintenance' => 'Pemeliharaan Sistem',
    'description' => 'Pantau backup, penggunaan disk, dan kelola pengarsipan log.',

    // Disk Usage
    'disk_usage' => 'Penggunaan Disk',
    'used' => 'Terpakai',
    'free' => 'Tersedia',
    'total' => 'Total',
    'capacity_used' => 'Kapasitas Terpakai',
    'disk_warning' => 'Penggunaan disk di atas 90%. Pertimbangkan untuk membersihkan backup lama atau menambah penyimpanan.',
    'total_backup_size' => 'Total Ukuran Backup',

    // Actions
    'actions' => 'Aksi Cepat',
    'run_full_backup' => 'Backup Penuh',
    'full_backup_desc' => 'Database + file penyimpanan',
    'run_db_backup' => 'Database Saja',
    'db_backup_desc' => 'Backup database cepat',
    'run_log_archive' => 'Arsipkan Log',
    'log_archive_desc' => 'Arsipkan log lebih dari 1 tahun',

    // Backups Table
    'recent_backups' => 'Backup Terbaru',
    'files' => 'file',
    'no_backups' => 'Tidak ada backup ditemukan.',
    'no_backups_hint' => 'Jalankan backup pertama menggunakan tombol di atas atau tunggu backup terjadwal.',
    'filename' => 'Nama File',
    'size' => 'Ukuran',
    'created' => 'Dibuat',
    'action' => 'Aksi',
    'latest' => 'Terbaru',
    'download' => 'Unduh',

    // Log Stats
    'active_logs' => 'Log Aktif',
    'archived_logs' => 'Log Terarsip',
    'records_in_activity_logs' => 'Data di tabel activity_logs',
    'records_in_archive' => 'Data di tabel arsip',

    // Schedule
    'schedule' => 'Jadwal Backup',
    'full_backup' => 'Backup Penuh',
    'db_backup' => 'Backup DB',
    'cleanup' => 'Pembersihan',
    'log_archive' => 'Arsip Log',
    'daily_at' => 'Harian pukul :time',
    'every_6h' => 'Setiap 6 jam',
    'weekly_sunday' => 'Mingguan (Minggu)',
    'retention_daily' => 'Backup harian disimpan selama :days hari',
    'retention_weekly' => 'Backup mingguan disimpan selama :weeks minggu',
    'retention_monthly' => 'Backup bulanan disimpan selama :months bulan',

    // Flash Messages
    'backup_not_found' => 'File backup tidak ditemukan.',
    'backup_started' => 'Backup :type telah berhasil dimulai.',
    'backup_failed' => 'Backup gagal: :error',
    'archive_complete' => 'Pengarsipan log selesai.',
    'archive_failed' => 'Pengarsipan log gagal: :error',
];
