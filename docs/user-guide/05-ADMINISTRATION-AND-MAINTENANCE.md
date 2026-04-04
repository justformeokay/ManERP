# Chapter 5 — Administration & Maintenance
# Bab 5 — Administrasi & Pemeliharaan
# 제5장 — 관리 및 유지보수
# 第5章 — 管理与维护

---

## 5.1 User Management

### 🇬🇧 English

**Roles & Permissions**

ManERP uses a role-based access control (RBAC) system with two primary roles:

| Role | Description | Permissions |
|------|-------------|-------------|
| **Admin** | Full system access | Create/edit/delete users, manage settings, view all modules, manage approvals, access system maintenance |
| **Staff** | Operational access | Access assigned modules, create transactions, view own data, submit for approval |

**SOP: Creating a New User**

1. Navigate to **Settings** → **Users** (`Users` / `Pengguna` / `사용자` / `用户`).
2. Click **"Add User"** (`Add User` / `Tambah Pengguna` / `사용자 추가` / `添加用户`).
3. Fill in the required fields:
   - **Name** — Full name of the user
   - **Email** — Used as login credential (must be unique)
   - **Password** — Minimum 8 characters (the system enforces complexity requirements)
   - **Role** — Select Admin or Staff
   - **Locale** — Default language preference (en/id/ko/zh)
4. Click **"Save"** (`Save`).
5. The new user can now log in with their email and password.

📸 **[SCREENSHOT: Add User form with Name, Email, Password, Role, and Locale fields — English UI]**

**SOP: Editing/Deactivating a User**

1. Navigate to **Settings** → **Users**.
2. Click the user row to open the detail view.
3. Edit fields as needed, or toggle the **Active** status to deactivate.
4. Click **"Update"** (`Update`).

> **Security Note:** Deactivated users cannot log in but their historical data (audit logs, transactions) is preserved for compliance.

---

### 🇮🇩 Bahasa Indonesia

**Peran & Hak Akses**

ManERP menggunakan sistem kontrol akses berbasis peran (RBAC) dengan dua peran utama:

| Peran | Deskripsi | Hak Akses |
|-------|-----------|-----------|
| **Admin** | Akses penuh ke sistem | Buat/edit/hapus pengguna, kelola pengaturan, lihat semua modul, kelola persetujuan, akses pemeliharaan sistem |
| **Staff** | Akses operasional | Akses modul yang ditugaskan, buat transaksi, lihat data sendiri, ajukan persetujuan |

**SOP: Membuat Pengguna Baru**

1. Navigasi ke **Pengaturan** → **Pengguna** (`Pengguna`).
2. Klik **"Tambah Pengguna"** (`Tambah Pengguna`).
3. Isi kolom yang diperlukan:
   - **Nama** — Nama lengkap pengguna
   - **Email** — Digunakan sebagai kredensial login (harus unik)
   - **Password** — Minimal 8 karakter (sistem menerapkan persyaratan kompleksitas)
   - **Peran** — Pilih Admin atau Staff
   - **Bahasa** — Preferensi bahasa default (en/id/ko/zh)
4. Klik **"Simpan"** (`Simpan`).
5. Pengguna baru sekarang dapat masuk dengan email dan kata sandi mereka.

📸 **[SCREENSHOT: Formulir Tambah Pengguna dengan kolom Nama, Email, Password, Peran, dan Bahasa — UI Bahasa Indonesia]**

**SOP: Mengedit/Menonaktifkan Pengguna**

1. Navigasi ke **Pengaturan** → **Pengguna**.
2. Klik baris pengguna untuk membuka tampilan detail.
3. Edit kolom sesuai kebutuhan, atau toggle status **Aktif** untuk menonaktifkan.
4. Klik **"Perbarui"** (`Perbarui`).

> **Catatan Keamanan:** Pengguna yang dinonaktifkan tidak dapat login tetapi data historis mereka (log audit, transaksi) tetap tersimpan untuk kepatuhan.

---

### 🇰🇷 한국어

**역할 및 권한**

| 역할 | 설명 | 권한 |
|------|------|------|
| **관리자** | 전체 시스템 접근 | 사용자 생성/편집/삭제, 설정 관리, 모든 모듈 접근, 승인 관리, 시스템 유지보수 접근 |
| **스태프** | 운영 접근 | 할당된 모듈 접근, 거래 생성, 본인 데이터 조회, 승인 요청 제출 |

**SOP: 신규 사용자 생성**

1. **설정** → **사용자** (`사용자`)로 이동합니다.
2. **"사용자 추가"** (`사용자 추가`)를 클릭합니다.
3. 필수 항목을 입력합니다:
   - **이름**, **이메일** (로그인 자격 증명), **비밀번호** (최소 8자), **역할** (관리자/스태프), **언어** (기본 언어 설정)
4. **"저장"** (`저장`)을 클릭합니다.

📸 **[SCREENSHOT: 이름, 이메일, 비밀번호, 역할, 언어 필드가 있는 사용자 추가 양식 — 한국어 UI]**

---

### 🇨🇳 中文

**角色与权限**

| 角色 | 描述 | 权限 |
|------|------|------|
| **管理员** | 完全系统访问 | 创建/编辑/删除用户、管理设置、访问所有模块、管理审批、访问系统维护 |
| **员工** | 运营访问 | 访问分配的模块、创建交易、查看自己的数据、提交审批 |

**SOP：创建新用户**

1. 导航至**设置** → **用户** (`用户`)。
2. 点击**"添加用户"** (`添加用户`)。
3. 填写必填项：**姓名**、**邮箱**（登录凭证）、**密码**（至少8位）、**角色**（管理员/员工）、**语言**（默认语言偏好）。
4. 点击**"保存"** (`保存`)。

📸 **[SCREENSHOT: 带姓名、邮箱、密码、角色和语言字段的添加用户表单 — 中文UI]**

---

## 5.2 System Maintenance

### 🇬🇧 English

**Accessing System Maintenance**

Navigate to **Settings** → **System Maintenance** (`System Maintenance` / `Pemeliharaan Sistem` / `시스템 유지보수` / `系统维护`).

📸 **[SCREENSHOT: System Maintenance dashboard showing disk usage, backup list, and action buttons — English UI]**

**Maintenance Dashboard Overview:**

| Widget | Description | UI Label (EN) | UI Label (ID) |
|--------|-------------|---------------|----------------|
| Disk Usage | Shows used/total disk space with progress bar | `Disk Usage` | `Penggunaan Disk` |
| Database Size | Current database size | `Database Size` | `Ukuran Database` |
| Last Backup | Date/time of the most recent backup | `Last Backup` | `Backup Terakhir` |
| Next Scheduled | Next scheduled backup time | `Next Scheduled Backup` | `Backup Terjadwal Berikutnya` |
| Recent Backups | List of available backup files | `Recent Backups` | `Backup Terbaru` |

**Manual Backup:**

1. On the Maintenance dashboard, click **"Run Backup Now"** (`Run Backup Now` / `Jalankan Backup Sekarang`).
2. The system creates a full backup (database + files) using `spatie/laravel-backup`.
3. Wait for the success notification.
4. The backup appears in the **Recent Backups** list.

**Manual Log Cleanup:**

1. Click **"Archive Logs"** (`Archive Logs` / `Arsipkan Log`).
2. Old log files are compressed and archived.
3. Monitor the **Log Files** section to verify cleanup.

> **Important:** Only users with **Admin** role have access to System Maintenance.

---

### 🇮🇩 Bahasa Indonesia

**Mengakses Pemeliharaan Sistem**

Navigasi ke **Pengaturan** → **Pemeliharaan Sistem** (`Pemeliharaan Sistem`).

📸 **[SCREENSHOT: Dashboard Pemeliharaan Sistem menampilkan penggunaan disk, daftar backup, dan tombol aksi — UI Bahasa Indonesia]**

**Ikhtisar Dashboard Pemeliharaan:**

| Widget | Deskripsi | Label UI |
|--------|-----------|----------|
| Penggunaan Disk | Menampilkan ruang disk terpakai/total dengan progress bar | `Penggunaan Disk` |
| Ukuran Database | Ukuran database saat ini | `Ukuran Database` |
| Backup Terakhir | Tanggal/waktu backup terbaru | `Backup Terakhir` |
| Backup Terjadwal | Waktu backup terjadwal berikutnya | `Backup Terjadwal Berikutnya` |
| Backup Terbaru | Daftar file backup yang tersedia | `Backup Terbaru` |

**Backup Manual:**

1. Di dashboard Pemeliharaan, klik **"Jalankan Backup Sekarang"** (`Jalankan Backup Sekarang`).
2. Sistem membuat backup penuh (database + file) menggunakan `spatie/laravel-backup`.
3. Tunggu notifikasi berhasil.
4. Backup akan muncul di daftar **Backup Terbaru**.

**Pembersihan Log Manual:**

1. Klik **"Arsipkan Log"** (`Arsipkan Log`).
2. File log lama dikompresi dan diarsipkan.

> **Penting:** Hanya pengguna dengan peran **Admin** yang memiliki akses ke Pemeliharaan Sistem.

---

### 🇰🇷 한국어

**시스템 유지보수 접근**

**설정** → **시스템 유지보수** (`시스템 유지보수`)로 이동합니다.

📸 **[SCREENSHOT: 디스크 사용량, 백업 목록, 작업 버튼이 있는 시스템 유지보수 대시보드 — 한국어 UI]**

| 위젯 | 설명 |
|------|------|
| 디스크 사용량 | 사용/전체 디스크 공간 표시 |
| 데이터베이스 크기 | 현재 데이터베이스 크기 |
| 마지막 백업 | 최근 백업 일시 |
| 다음 예정 백업 | 다음 예정된 백업 시간 |
| 최근 백업 | 사용 가능한 백업 파일 목록 |

**수동 백업:** **"지금 백업 실행"**을 클릭합니다. 시스템이 전체 백업(데이터베이스 + 파일)을 생성합니다.

**수동 로그 정리:** **"로그 아카이브"**를 클릭합니다. 이전 로그 파일이 압축 및 보관됩니다.

---

### 🇨🇳 中文

**访问系统维护**

导航至**设置** → **系统维护** (`系统维护`)。

📸 **[SCREENSHOT: 显示磁盘使用量、备份列表和操作按钮的系统维护仪表板 — 中文UI]**

| 组件 | 描述 |
|------|------|
| 磁盘使用量 | 显示已用/总磁盘空间 |
| 数据库大小 | 当前数据库大小 |
| 最近备份 | 最近备份日期/时间 |
| 下次计划备份 | 下次计划备份时间 |
| 最近备份列表 | 可用备份文件列表 |

**手动备份：** 点击**"立即运行备份"**，系统创建完整备份（数据库+文件）。

**手动日志清理：** 点击**"归档日志"**，旧日志文件被压缩并归档。

---

## 5.3 Automated Backup & Log Rotation

### 🇬🇧 English

ManERP includes an automated backup and log rotation system configured via Laravel's task scheduler.

**Automated Backup Schedule:**

| Schedule | Type | Time | Description |
|----------|------|------|-------------|
| Daily Full Backup | Database + Files | 02:00 AM | Complete system backup |
| Every 6 Hours | Database Only | 00:00, 06:00, 12:00, 18:00 | Database snapshot |

**Backup Retention Policy:**
- Keep backups for a configured number of days (default: 7 days)
- The oldest backup is automatically deleted when the retention limit is reached

**Log Rotation:**
- The `log:archive` Artisan command compresses old log files
- Scheduled to run automatically (weekly by default)
- Archived logs are stored in `storage/app/log-archives/`

**Monitoring:**
- The System Maintenance dashboard shows the next scheduled backup time
- Backup failures trigger notifications to Admin users

📸 **[SCREENSHOT: Backup schedule configuration and retention settings — English UI]**

> **Best Practice:** Periodically download backups to an external/off-site location. ManERP keeps backups on the same server by default — off-site copies protect against hardware failure.

---

### 🇮🇩 Bahasa Indonesia

ManERP menyertakan sistem backup otomatis dan rotasi log yang dikonfigurasi melalui penjadwal tugas Laravel.

**Jadwal Backup Otomatis:**

| Jadwal | Jenis | Waktu | Deskripsi |
|--------|-------|-------|-----------|
| Backup Penuh Harian | Database + File | 02.00 | Backup sistem lengkap |
| Setiap 6 Jam | Database Saja | 00:00, 06:00, 12:00, 18:00 | Snapshot database |

**Kebijakan Retensi Backup:**
- Simpan backup selama jumlah hari yang dikonfigurasi (default: 7 hari)
- Backup terlama dihapus secara otomatis saat batas retensi tercapai

**Rotasi Log:**
- Perintah Artisan `log:archive` mengkompresi file log lama
- Dijadwalkan berjalan otomatis (mingguan secara default)
- Log yang diarsipkan disimpan di `storage/app/log-archives/`

📸 **[SCREENSHOT: Konfigurasi jadwal backup dan pengaturan retensi — UI Bahasa Indonesia]**

> **Praktik Terbaik:** Unduh backup secara berkala ke lokasi eksternal/off-site. ManERP menyimpan backup di server yang sama secara default — salinan off-site melindungi dari kegagalan perangkat keras.

---

### 🇰🇷 한국어

**자동 백업 일정:**

| 일정 | 유형 | 시간 | 설명 |
|------|------|------|------|
| 일일 전체 백업 | DB + 파일 | 오전 2시 | 전체 시스템 백업 |
| 6시간마다 | DB만 | 00:00, 06:00, 12:00, 18:00 | 데이터베이스 스냅샷 |

- **보존 정책:** 설정된 일수만큼 백업 보관 (기본: 7일)
- **로그 순환:** `log:archive` 명령어가 이전 로그 파일을 압축 (주간 실행)
- **아카이브 위치:** `storage/app/log-archives/`

📸 **[SCREENSHOT: 백업 일정 구성 및 보존 설정 — 한국어 UI]**

> **모범 사례:** 주기적으로 백업을 외부/오프사이트 위치에 다운로드하십시오.

---

### 🇨🇳 中文

**自动备份计划：**

| 计划 | 类型 | 时间 | 描述 |
|------|------|------|------|
| 每日完整备份 | 数据库+文件 | 凌晨2:00 | 完整系统备份 |
| 每6小时 | 仅数据库 | 00:00, 06:00, 12:00, 18:00 | 数据库快照 |

- **保留策略：** 保留配置天数的备份（默认：7天）
- **日志轮转：** `log:archive` 命令压缩旧日志文件（每周运行）
- **归档位置：** `storage/app/log-archives/`

📸 **[SCREENSHOT: 备份计划配置和保留设置 — 中文UI]**

> **最佳实践：** 定期将备份下载到外部/异地位置。ManERP默认将备份保存在同一服务器上——异地副本可防止硬件故障。
