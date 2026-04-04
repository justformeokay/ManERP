# Chapter 2 — Security & Authentication
# Bab 2 — Keamanan & Autentikasi
# 제2장 — 보안 및 인증
# 第2章 — 安全与认证

---

## 2.1 Login & Session Management

### 🇬🇧 English

**SOP: Logging In to ManERP**

1. Open ManERP in your web browser.
2. Enter your **Email** address in the email field.
3. Enter your **Password** in the password field.
4. Optionally check **"Remember me"** to extend your session.
5. Click the **"Log in"** button.
6. If Multi-Factor Authentication (MFA) is enabled on your account, you will be redirected to the **Two-Factor Challenge** screen (see Section 2.2).

📸 **[SCREENSHOT: Login page with email/password fields and "Log in" button — English UI]**

**Session Security:**
- Sessions expire after a configurable idle timeout.
- All login activity is recorded in the **Audit Logs** (`Audit Logs`) for compliance.
- Unauthorized access attempts are logged with IP address and timestamp.

---

### 🇮🇩 Bahasa Indonesia

**SOP: Masuk ke ManERP**

1. Buka ManERP di peramban web Anda.
2. Masukkan alamat **Email** pada kolom email.
3. Masukkan **Kata Sandi** pada kolom kata sandi.
4. Opsional: centang **"Ingat saya"** untuk memperpanjang sesi.
5. Klik tombol **"Masuk"**.
6. Jika Autentikasi Multi-Faktor (MFA) telah diaktifkan pada akun Anda, Anda akan diarahkan ke layar **Tantangan Dua Faktor** (lihat Bagian 2.2).

📸 **[SCREENSHOT: Halaman masuk dengan kolom email/kata sandi dan tombol "Masuk" — UI Bahasa Indonesia]**

**Keamanan Sesi:**
- Sesi berakhir setelah batas waktu tidak aktif yang dapat dikonfigurasi.
- Semua aktivitas masuk dicatat dalam **Log Audit** (`Log Audit`) untuk kepatuhan.
- Upaya akses tidak sah dicatat beserta alamat IP dan cap waktu.

---

### 🇰🇷 한국어

**SOP: ManERP 로그인**

1. 웹 브라우저에서 ManERP를 엽니다.
2. 이메일 필드에 **이메일** 주소를 입력합니다.
3. 비밀번호 필드에 **비밀번호**를 입력합니다.
4. 선택 사항: **"로그인 유지"**를 체크하여 세션을 연장합니다.
5. **"로그인"** 버튼을 클릭합니다.
6. 계정에 다중 인증(MFA)이 활성화된 경우, **2단계 인증** 화면으로 이동합니다 (2.2절 참조).

📸 **[SCREENSHOT: 이메일/비밀번호 필드와 "로그인" 버튼이 있는 로그인 페이지 — 한국어 UI]**

**세션 보안:**
- 설정 가능한 유휴 시간 초과 후 세션이 만료됩니다.
- 모든 로그인 활동은 규정 준수를 위해 **감사 로그** (`감사 로그`)에 기록됩니다.
- 무단 접근 시도는 IP 주소 및 타임스탬프와 함께 기록됩니다.

---

### 🇨🇳 中文

**SOP：登录ManERP**

1. 在Web浏览器中打开ManERP。
2. 在邮箱字段中输入您的**邮箱地址**。
3. 在密码字段中输入您的**密码**。
4. 可选：勾选**"记住我"**以延长会话时间。
5. 点击**"登录"**按钮。
6. 如果您的账户已启用多因素认证（MFA），您将被重定向到**双因素验证**页面（参见2.2节）。

📸 **[SCREENSHOT: 带邮箱/密码字段和"登录"按钮的登录页面 — 中文UI]**

**会话安全：**
- 会话在可配置的空闲超时后失效。
- 所有登录活动记录在**审计日志** (`审计日志`) 中以确保合规。
- 未授权访问尝试将连同IP地址和时间戳一并记录。

---

## 2.2 Multi-Factor Authentication (MFA / 2FA) Setup

### 🇬🇧 English

**SOP: Setting Up Two-Factor Authentication**

ManERP supports TOTP-based (Time-based One-Time Password) two-factor authentication compatible with authenticator apps such as **Google Authenticator**, **Authy**, or **Microsoft Authenticator**.

**Step-by-Step Setup:**

1. Navigate to your **Profile Settings** (`Profile Settings`) by clicking your avatar → **Profile Settings**.
2. Locate the **Two-Factor Authentication** section.
3. Click the **"Setup Two-Factor Authentication"** button.
4. A **QR Code** will be displayed on screen:

   📸 **[SCREENSHOT: Two-Factor setup page showing QR code and secret key — English UI]**

5. Open your authenticator app on your mobile device.
6. Scan the QR code, or manually enter the **Secret Key** displayed below the QR code.
7. Enter the **6-digit verification code** from your authenticator app into the confirmation field.
8. Click **"Enable"** to activate 2FA.
9. **IMPORTANT:** The system will display **8 recovery codes**. Save these codes in a secure location. Each code can be used once to bypass 2FA if you lose access to your authenticator device.

   📸 **[SCREENSHOT: Recovery codes display after enabling 2FA — English UI]**

**Using 2FA at Login:**

1. After entering your email and password, you will see the **Two-Factor Challenge** screen.
2. Enter the **6-digit code** from your authenticator app.
3. Alternatively, click **"Use a recovery code"** and enter one of your saved recovery codes.
4. Click **"Verify"** to complete login.

   📸 **[SCREENSHOT: Two-Factor Challenge screen with code entry field — English UI]**

**Disabling 2FA:**

1. Go to **Profile Settings** → Two-Factor Authentication section.
2. Click **"Disable Two-Factor Authentication"**.
3. Confirm the action with your password.

> ⚠️ **Security Recommendation:** All users with admin or finance access should enable MFA. This is critically important for protecting sensitive financial and employee data.

---

### 🇮🇩 Bahasa Indonesia

**SOP: Mengatur Autentikasi Dua Faktor (2FA)**

ManERP mendukung autentikasi dua faktor berbasis TOTP (Time-based One-Time Password) yang kompatibel dengan aplikasi autentikator seperti **Google Authenticator**, **Authy**, atau **Microsoft Authenticator**.

**Langkah-langkah Pengaturan:**

1. Navigasi ke **Pengaturan Profil** (`Pengaturan Profil`) dengan mengklik avatar → **Pengaturan Profil**.
2. Temukan bagian **Autentikasi Dua Faktor**.
3. Klik tombol **"Atur Autentikasi Dua Faktor"**.
4. **Kode QR** akan ditampilkan di layar:

   📸 **[SCREENSHOT: Halaman pengaturan 2FA menampilkan kode QR dan kunci rahasia — UI Bahasa Indonesia]**

5. Buka aplikasi autentikator di perangkat seluler Anda.
6. Pindai kode QR, atau masukkan **Kunci Rahasia** secara manual yang ditampilkan di bawah kode QR.
7. Masukkan **kode verifikasi 6 digit** dari aplikasi autentikator ke kolom konfirmasi.
8. Klik **"Aktifkan"** untuk mengaktifkan 2FA.
9. **PENTING:** Sistem akan menampilkan **8 kode pemulihan**. Simpan kode-kode ini di tempat yang aman. Setiap kode hanya dapat digunakan sekali untuk melewati 2FA jika Anda kehilangan akses ke perangkat autentikator.

   📸 **[SCREENSHOT: Tampilan kode pemulihan setelah mengaktifkan 2FA — UI Bahasa Indonesia]**

**Menggunakan 2FA saat Masuk:**

1. Setelah memasukkan email dan kata sandi, Anda akan melihat layar **Tantangan Dua Faktor**.
2. Masukkan **kode 6 digit** dari aplikasi autentikator.
3. Alternatif: klik **"Gunakan kode pemulihan"** dan masukkan salah satu kode yang telah disimpan.
4. Klik **"Verifikasi"** untuk menyelesaikan proses masuk.

   📸 **[SCREENSHOT: Layar Tantangan Dua Faktor dengan kolom input kode — UI Bahasa Indonesia]**

**Menonaktifkan 2FA:**

1. Buka **Pengaturan Profil** → bagian Autentikasi Dua Faktor.
2. Klik **"Nonaktifkan Autentikasi Dua Faktor"**.
3. Konfirmasi dengan kata sandi Anda.

> ⚠️ **Rekomendasi Keamanan:** Semua pengguna dengan akses admin atau keuangan sebaiknya mengaktifkan MFA. Hal ini sangat penting untuk melindungi data keuangan dan karyawan yang sensitif.

---

### 🇰🇷 한국어

**SOP: 2단계 인증(2FA) 설정**

ManERP는 **Google Authenticator**, **Authy**, **Microsoft Authenticator** 등의 인증 앱과 호환되는 TOTP(시간 기반 일회용 비밀번호) 기반의 2단계 인증을 지원합니다.

**단계별 설정 안내:**

1. 아바타 클릭 → **프로필 설정** (`프로필 설정`)으로 이동합니다.
2. **2단계 인증** 섹션을 찾습니다.
3. **"2단계 인증 설정"** 버튼을 클릭합니다.
4. 화면에 **QR 코드**가 표시됩니다:

   📸 **[SCREENSHOT: QR 코드와 비밀 키가 표시된 2단계 인증 설정 페이지 — 한국어 UI]**

5. 모바일 기기에서 인증 앱을 엽니다.
6. QR 코드를 스캔하거나, QR 코드 아래에 표시된 **비밀 키**를 수동으로 입력합니다.
7. 인증 앱에서 표시되는 **6자리 인증 코드**를 확인 필드에 입력합니다.
8. **"활성화"**를 클릭하여 2FA를 활성화합니다.
9. **중요:** 시스템이 **8개의 복구 코드**를 표시합니다. 이 코드를 안전한 곳에 보관하십시오. 각 코드는 인증 장치에 접근할 수 없을 때 한 번만 사용할 수 있습니다.

   📸 **[SCREENSHOT: 2FA 활성화 후 표시되는 복구 코드 — 한국어 UI]**

**로그인 시 2FA 사용:**

1. 이메일과 비밀번호 입력 후 **2단계 인증** 화면이 표시됩니다.
2. 인증 앱의 **6자리 코드**를 입력합니다.
3. 또는 **"복구 코드 사용"**을 클릭하여 저장된 복구 코드 중 하나를 입력합니다.
4. **"확인"**을 클릭하여 로그인을 완료합니다.

   📸 **[SCREENSHOT: 코드 입력 필드가 있는 2단계 인증 화면 — 한국어 UI]**

**2FA 비활성화:**

1. **프로필 설정** → 2단계 인증 섹션으로 이동합니다.
2. **"2단계 인증 비활성화"**를 클릭합니다.
3. 비밀번호로 확인합니다.

> ⚠️ **보안 권장 사항:** 관리자 또는 재무 접근 권한이 있는 모든 사용자는 MFA를 활성화해야 합니다. 민감한 재무 및 직원 데이터 보호에 매우 중요합니다.

---

### 🇨🇳 中文

**SOP：设置双因素认证（2FA）**

ManERP支持基于TOTP（基于时间的一次性密码）的双因素认证，兼容 **Google Authenticator**、**Authy** 或 **Microsoft Authenticator** 等认证应用。

**逐步设置指南：**

1. 点击头像 → **个人设置** (`个人设置`)。
2. 找到**双因素认证**部分。
3. 点击**"设置双因素认证"**按钮。
4. 屏幕上将显示**二维码**：

   📸 **[SCREENSHOT: 显示二维码和密钥的双因素认证设置页面 — 中文UI]**

5. 在移动设备上打开认证应用。
6. 扫描二维码，或手动输入二维码下方显示的**密钥**。
7. 将认证应用中显示的**6位验证码**输入确认字段。
8. 点击**"启用"**以激活2FA。
9. **重要提示：** 系统将显示**8个恢复代码**。请将这些代码保存在安全位置。每个代码在您无法访问认证设备时只能使用一次。

   📸 **[SCREENSHOT: 启用2FA后显示的恢复代码 — 中文UI]**

**登录时使用2FA：**

1. 输入邮箱和密码后，您将看到**双因素验证**页面。
2. 输入认证应用中的**6位代码**。
3. 或者点击**"使用恢复代码"**并输入您保存的恢复代码之一。
4. 点击**"验证"**完成登录。

   📸 **[SCREENSHOT: 带代码输入字段的双因素验证页面 — 中文UI]**

**禁用2FA：**

1. 前往**个人设置** → 双因素认证部分。
2. 点击**"禁用双因素认证"**。
3. 使用密码确认操作。

> ⚠️ **安全建议：** 所有具有管理员或财务访问权限的用户应启用MFA。这对保护敏感的财务和员工数据至关重要。

---

## 2.3 Password Policy

### 🇬🇧 English

**Password Requirements:**

| Requirement | Policy |
|-------------|--------|
| Minimum length | 8 characters |
| Character types | Must include uppercase, lowercase, numbers, and special characters |
| Password expiry | Recommended: every 90 days |
| History | Cannot reuse the last 5 passwords |

**Changing Your Password:**

1. Navigate to **Profile Settings** (`Profile Settings`).
2. Click **"Update Password"** section.
3. Enter your **Current Password**.
4. Enter your **New Password** and confirm it.
5. Click **"Save"** (`Save`).

📸 **[SCREENSHOT: Profile page showing password change form — English UI]**

---

### 🇮🇩 Bahasa Indonesia

**Persyaratan Kata Sandi:**

| Persyaratan | Kebijakan |
|-------------|-----------|
| Panjang minimum | 8 karakter |
| Jenis karakter | Wajib mengandung huruf besar, huruf kecil, angka, dan karakter khusus |
| Kedaluwarsa kata sandi | Direkomendasikan: setiap 90 hari |
| Riwayat | Tidak dapat menggunakan kembali 5 kata sandi terakhir |

**Mengubah Kata Sandi:**

1. Navigasi ke **Pengaturan Profil** (`Pengaturan Profil`).
2. Klik bagian **"Perbarui Kata Sandi"**.
3. Masukkan **Kata Sandi Saat Ini**.
4. Masukkan **Kata Sandi Baru** dan konfirmasi.
5. Klik **"Simpan"** (`Simpan`).

📸 **[SCREENSHOT: Halaman profil menampilkan formulir ubah kata sandi — UI Bahasa Indonesia]**

---

### 🇰🇷 한국어

**비밀번호 요구 사항:**

| 요구 사항 | 정책 |
|-----------|------|
| 최소 길이 | 8자 |
| 문자 유형 | 대문자, 소문자, 숫자, 특수 문자 포함 필수 |
| 비밀번호 만료 | 권장: 90일마다 |
| 이력 | 최근 5개 비밀번호 재사용 불가 |

**비밀번호 변경:**

1. **프로필 설정** (`프로필 설정`)으로 이동합니다.
2. **"비밀번호 변경"** 섹션을 클릭합니다.
3. **현재 비밀번호**를 입력합니다.
4. **새 비밀번호**를 입력하고 확인합니다.
5. **"저장"** (`저장`)을 클릭합니다.

📸 **[SCREENSHOT: 비밀번호 변경 양식이 있는 프로필 페이지 — 한국어 UI]**

---

### 🇨🇳 中文

**密码要求：**

| 要求 | 策略 |
|------|------|
| 最小长度 | 8个字符 |
| 字符类型 | 必须包含大写字母、小写字母、数字和特殊字符 |
| 密码过期 | 建议：每90天 |
| 历史记录 | 不能重复使用最近5个密码 |

**更改密码：**

1. 导航至**个人设置** (`个人设置`)。
2. 点击**"更新密码"**部分。
3. 输入**当前密码**。
4. 输入**新密码**并确认。
5. 点击**"保存"** (`保存`)。

📸 **[SCREENSHOT: 显示密码更改表单的个人页面 — 中文UI]**

---

## 2.4 Audit Logs & Integrity Verification

### 🇬🇧 English

**Understanding Audit Logs** (`Audit Logs`)

Every user action in ManERP is automatically recorded with a tamper-proof HMAC-SHA256 checksum. This ensures complete traceability and data integrity.

**What is logged:**
- All create, update, and delete operations
- Module name, action type, and description
- User who performed the action
- IP address and session information
- Timestamp with timezone
- Previous and new values for changed fields

**Viewing Audit Logs:**

1. Navigate to **Audit Logs** (`Audit Logs`) in the Administration section of the sidebar (Admin only).
2. Browse the chronological list of all system activities.
3. Click on any log entry to view the full detail including old/new value comparison.

📸 **[SCREENSHOT: Audit Logs index page showing list of system activities with filters — English UI]**

**Verify Integrity — Why It Matters:**

The **"Verify Integrity"** function checks whether any audit log record has been tampered with after creation. Each record's HMAC-SHA256 checksum is recomputed and compared against the stored checksum.

1. On the **Audit Logs** page, click the **"Verify Integrity"** button.
2. The system will scan all records and report:
   - ✅ **Passed:** All checksums match — data integrity confirmed.
   - ❌ **Failed:** One or more records may have been altered outside the application.
3. If failures are detected, investigate immediately as this could indicate unauthorized database access.

📸 **[SCREENSHOT: Verify Integrity results showing all records passed — English UI]**

> ⚠️ **Critical:** Integrity verification is essential for regulatory compliance, financial auditing, and forensic investigation. Run this check regularly (recommended: weekly).

---

### 🇮🇩 Bahasa Indonesia

**Memahami Log Audit** (`Log Audit`)

Setiap tindakan pengguna di ManERP dicatat secara otomatis dengan checksum HMAC-SHA256 yang tahan perubahan. Ini menjamin keterlacakan dan integritas data secara menyeluruh.

**Apa yang dicatat:**
- Semua operasi buat, ubah, dan hapus
- Nama modul, jenis aksi, dan deskripsi
- Pengguna yang melakukan aksi
- Alamat IP dan informasi sesi
- Cap waktu dengan zona waktu
- Nilai sebelum dan sesudah perubahan

**Melihat Log Audit:**

1. Navigasi ke **Log Audit** (`Log Audit`) di bagian Administrasi pada sidebar (hanya Admin).
2. Telusuri daftar kronologis semua aktivitas sistem.
3. Klik pada entri log untuk melihat detail lengkap termasuk perbandingan nilai lama/baru.

📸 **[SCREENSHOT: Halaman indeks Log Audit menampilkan daftar aktivitas sistem dengan filter — UI Bahasa Indonesia]**

**Verifikasi Integritas — Mengapa Ini Penting:**

Fungsi **"Verifikasi Integritas"** memeriksa apakah ada catatan log audit yang telah dimanipulasi setelah pembuatan. Checksum HMAC-SHA256 setiap catatan dihitung ulang dan dibandingkan dengan checksum yang tersimpan.

1. Pada halaman **Log Audit**, klik tombol **"Verifikasi Integritas"**.
2. Sistem akan memindai semua catatan dan melaporkan:
   - ✅ **Lolos:** Semua checksum cocok — integritas data terkonfirmasi.
   - ❌ **Gagal:** Satu atau lebih catatan mungkin telah diubah di luar aplikasi.
3. Jika kegagalan terdeteksi, lakukan investigasi segera karena ini bisa mengindikasikan akses database yang tidak sah.

📸 **[SCREENSHOT: Hasil Verifikasi Integritas menampilkan semua catatan lolos — UI Bahasa Indonesia]**

> ⚠️ **Penting:** Verifikasi integritas sangat penting untuk kepatuhan regulasi, audit keuangan, dan investigasi forensik. Jalankan pemeriksaan ini secara berkala (direkomendasikan: mingguan).

---

### 🇰🇷 한국어

**감사 로그 이해** (`감사 로그`)

ManERP의 모든 사용자 작업은 변조 방지 HMAC-SHA256 체크섬으로 자동 기록됩니다. 이를 통해 완전한 추적 가능성과 데이터 무결성을 보장합니다.

**기록되는 내용:**
- 모든 생성, 수정 및 삭제 작업
- 모듈 이름, 작업 유형 및 설명
- 작업을 수행한 사용자
- IP 주소 및 세션 정보
- 시간대가 포함된 타임스탬프
- 변경된 필드의 이전 값과 새 값

**감사 로그 보기:**

1. 사이드바의 관리 섹션에서 **감사 로그** (`감사 로그`)로 이동합니다 (관리자만).
2. 모든 시스템 활동의 시간순 목록을 탐색합니다.
3. 로그 항목을 클릭하여 이전/이후 값 비교를 포함한 전체 세부 정보를 봅니다.

📸 **[SCREENSHOT: 필터가 있는 시스템 활동 목록을 보여주는 감사 로그 색인 페이지 — 한국어 UI]**

**무결성 검증 — 왜 중요한가:**

**"무결성 검증"** 기능은 감사 로그 기록이 생성 후 변조되었는지 확인합니다. 각 기록의 HMAC-SHA256 체크섬을 재계산하여 저장된 체크섬과 비교합니다.

1. **감사 로그** 페이지에서 **"무결성 검증"** 버튼을 클릭합니다.
2. 시스템이 모든 기록을 스캔하고 보고합니다:
   - ✅ **통과:** 모든 체크섬이 일치 — 데이터 무결성 확인됨.
   - ❌ **실패:** 하나 이상의 기록이 애플리케이션 외부에서 변경되었을 수 있음.
3. 실패가 감지되면 무단 데이터베이스 접근을 나타낼 수 있으므로 즉시 조사하십시오.

📸 **[SCREENSHOT: 모든 기록이 통과된 무결성 검증 결과 — 한국어 UI]**

> ⚠️ **중요:** 무결성 검증은 규정 준수, 재무 감사 및 포렌식 조사에 필수적입니다. 이 검사를 정기적으로 실행하십시오 (권장: 주 1회).

---

### 🇨🇳 中文

**理解审计日志** (`审计日志`)

ManERP中的每个用户操作都会自动记录，并附带防篡改的HMAC-SHA256校验和。这确保了完整的可追溯性和数据完整性。

**记录内容：**
- 所有创建、修改和删除操作
- 模块名称、操作类型和描述
- 执行操作的用户
- IP地址和会话信息
- 带时区的时间戳
- 更改字段的先前值和新值

**查看审计日志：**

1. 导航至侧边栏管理部分的**审计日志** (`审计日志`)（仅管理员）。
2. 浏览所有系统活动的时间顺序列表。
3. 点击任何日志条目查看完整详情，包括旧值/新值比较。

📸 **[SCREENSHOT: 显示系统活动列表和筛选器的审计日志索引页 — 中文UI]**

**完整性验证 — 为何重要：**

**"完整性验证"**功能检查审计日志记录在创建后是否被篡改。系统重新计算每条记录的HMAC-SHA256校验和，并与存储的校验和进行比较。

1. 在**审计日志**页面，点击**"完整性验证"**按钮。
2. 系统将扫描所有记录并报告：
   - ✅ **通过：** 所有校验和匹配 — 数据完整性已确认。
   - ❌ **失败：** 一条或多条记录可能在应用程序外部被修改。
3. 如果检测到失败，请立即调查，因为这可能表示未经授权的数据库访问。

📸 **[SCREENSHOT: 显示所有记录通过的完整性验证结果 — 中文UI]**

> ⚠️ **重要提示：** 完整性验证对于法规合规、财务审计和数字取证调查至关重要。请定期运行此检查（建议：每周一次）。
