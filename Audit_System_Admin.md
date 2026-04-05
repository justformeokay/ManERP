# 🔒 ManERP — System Administration & Security Perimeter Audit

**Audit Scope:** Pre-Mobile API Gateway Review
**Standard:** OWASP Top 10, ITGC, ISO 27001 Controls
**ManERP Version:** Phase 5 (244 tests / 880 assertions)

---

## PILLAR 1 — RBAC & Identity Management

### 1.1 Permission Architecture


| Aspect           | Status                                           | Detail                                                   |
| ---------------- | ------------------------------------------------ | -------------------------------------------------------- |
| Permission Model | Custom 2-tier                                    | `admin` (bypass all) / `staff` (explicit JSON array)     |
| Granularity      | 13 modules × 4 actions                          | 52 discrete permissions                                  |
| Storage          | `permissions` JSON column                        | Cast as array on User model                              |
| Gate Enforcement | Middleware`CheckPermission`                      | Route-level via`permission:module.action`                |
| Admin Bypass     | `hasPermission()` returns `true` for `isAdmin()` | **Design choice — no segregation of duties for admins** |

**Positive Findings:**

- Permission validation uses `Rule::in(User::allPermissions())` — prevents injection of non-existent permissions
- Staff users cannot escalate their own role (no self-edit escape)
- Admins set `permissions = null` (all-access) — clean separation from explicit staff arrays

**Findings:**


| #    | Severity   | Finding                                               | Evidence                                                                                                 |
| ---- | ---------- | ----------------------------------------------------- | -------------------------------------------------------------------------------------------------------- |
| F-01 | **MEDIUM** | No salary/payroll permission separation in`hr` module | `hr.view` grants access to both employee records AND payroll data. No `payroll.view` vs `hr.view` split. |
| F-02 | **LOW**    | Hardcoded primary admin protection`$user->id === 1`   | UserController.php — relies on auto-increment ID rather than a role flag or`is_super_admin` column      |
| F-03 | **INFO**   | Admin bypass has no audit differentiation             | When an admin accesses a resource, there's no log distinguishing "explicit permission" vs "admin bypass" |

### 1.2 Authentication Stack


| Layer                | Implementation                                                          | Strength                   |
| -------------------- | ----------------------------------------------------------------------- | -------------------------- |
| Password Policy      | `min(8)->letters()->mixedCase()->numbers()->symbols()->uncompromised()` | **ITGC Compliant** ✅      |
| Password Rotation    | 90-day via`ForcePasswordChange` middleware                              | ✅                         |
| Login Rate Limiting  | 5 attempts per `email                                                   | IP`, fires `Lockout` event |
| 2FA                  | TOTP via Google2FA, recovery codes,`EnsureTwoFactorVerified` middleware | ✅                         |
| Session Regeneration | On login (`LoginRequest`) and logout                                    | ✅                         |
| Active User Check    | `EnsureUserIsActive` — mid-session deactivation triggers logout        | ✅                         |
| Bcrypt Rounds        | 12 (.env.example)                                                       | ✅                         |

**Findings:**


| #    | Severity   | Finding                                          | Evidence                                                                                                                                                          |
| ---- | ---------- | ------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| F-04 | **MEDIUM** | Session encryption disabled                      | .env.example:`SESSION_ENCRYPT=false`, session.php defaults to `false`                                                                                             |
| F-05 | **MEDIUM** | `SESSION_SECURE_COOKIE` absent from .env.example | session.php`'secure'` has no default — cookies transmit over HTTP                                                                                                |
| F-06 | **LOW**    | `password_timeout` = 10,800s (3 hours)           | auth.php — for an ERP, 30-60 min is recommended                                                                                                                  |
| F-07 | **LOW**    | No rate limiting on 2FA challenge endpoint       | Authentication is rate-limited, but TOTP verification has no brute-force protection. 6-digit TOTP = 1M possibilities                                              |
| F-08 | **LOW**    | `RegisteredUserController` dead code             | File exists from Breeze scaffolding but no route references it. Comment in auth.php L20 confirms intentional disable. Should be deleted to reduce attack surface. |
| F-09 | **INFO**   | 2FA not mandatory for admins                     | `EnsureTwoFactorVerified` only checks if user *chose* to enable 2FA. Admins can operate without 2FA.                                                              |

### 1.3 Middleware Chain (Order of Execution)

```
Request → SecurityHeaders → EnsureUserIsActive → SetLocale → ApplyTimezone
        → AuthenticateSession → ForcePasswordChange → EnsureTwoFactorVerified
        → EnsureLicenseValid → [Route Middleware: admin/permission/fiscal-lock]
```

**Assessment:** Well-layered. Security headers applied first (even to error pages). Active-user check before any business logic. Password/2FA enforcement before license check. **Order is correct.**

### 1.4 Security Headers


| Header                 | Value                                              | Grade                                     |
| ---------------------- | -------------------------------------------------- | ----------------------------------------- |
| X-Frame-Options        | `SAMEORIGIN`                                       | ✅                                        |
| X-Content-Type-Options | `nosniff`                                          | ✅                                        |
| X-XSS-Protection       | `1; mode=block`                                    | ✅                                        |
| Referrer-Policy        | `strict-origin-when-cross-origin`                  | ✅                                        |
| HSTS                   | `max-age=31536000; includeSubDomains` (when HTTPS) | ✅                                        |
| Permissions-Policy     | Disables camera/mic/geolocation/payment            | ✅                                        |
| CSP                    | Full policy with dev-mode localhost:5173 exception | ✅                                        |
| Session Serialization  | `json` (not PHP)                                   | ✅ Mitigates gadget-chain deserialization |

---

## PILLAR 2 — System Settings & Global Configuration

### 2.1 Settings Architecture


| Aspect         | Detail                                                                      |
| -------------- | --------------------------------------------------------------------------- |
| Model          | Key-value store via`Setting` model                                          |
| Caching        | `Cache::rememberForever()` — flushed on update                             |
| Access Control | Admin-only routes (guarded by`admin` middleware)                            |
| Validation     | Present —`timezone` rule, `tax_rate 0-100`, `email`, `max:255` constraints |

**Settings Managed:**
`company_name`, `company_email`, `company_phone`, `company_address`, `default_currency`, `timezone`, `default_payment_terms`, `default_tax_rate`, `low_stock_threshold`, `items_per_page`

**Findings:**


| #    | Severity   | Finding                                 | Evidence                                                                                                                                                                                |
| ---- | ---------- | --------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| F-10 | **HIGH**   | No audit trail on settings changes      | SettingsController.php`update()` calls `Setting::setMany()` without `AuditLogService::log()`. Tax rate changes, currency switches, and timezone changes are **completely untraceable**. |
| F-11 | **MEDIUM** | No settings versioning / rollback       | Overwritten in-place. A mistake (e.g. tax rate changed from 11% to 1.1%) cannot be rolled back. Historical invoices may interpret tax rate retroactively.                               |
| F-12 | **MEDIUM** | BPJS rates hardcoded in`PayrollService` | `BPJS_JKK_RATE`, `BPJS_JKM_RATE`, etc. are PHP constants — require code deploy to update when government regulation changes                                                            |
| F-13 | **LOW**    | No validation of currency change impact | Changing`default_currency` from IDR → USD mid-operation has no warning about existing transactions denominated in the old currency                                                     |

---

## PILLAR 3 — Audit Log Management & HMAC Integrity

### 3.1 HMAC Implementation


| Aspect            | Detail                                                                               | Grade |
| ----------------- | ------------------------------------------------------------------------------------ | ----- |
| Algorithm         | HMAC-SHA256                                                                          | ✅    |
| Secret            | `config('app.key')` (APP_KEY from .env)                                              | ✅    |
| Payload (Phase 5) | `[user_id, module, action, description, ip_address, created_at, old_data, new_data]` | ✅    |
| Comparison        | `hash_equals()` (timing-safe)                                                        | ✅    |
| Immutability      | Eloquent`updating`/`deleting` hooks throw `RuntimeException`                         | ✅    |
| Verification      | `/audit-logs/verify` endpoint, 500-record chunks                                     | ✅    |
| Self-auditing     | Integrity check itself creates an audit log entry                                    | ✅    |

### 3.2 Audit Coverage Matrix


| Module           | Logged Actions                                       | Coverage    |
| ---------------- | ---------------------------------------------------- | ----------- |
| Auth             | login, logout, failed_login, lockout, password_reset | ✅ Complete |
| Inventory        | stock adjustments, transfers (with`sourceable`)      | ✅ Phase 5  |
| Accounting       | journal entries, period close/reopen                 | ✅ Phase 5  |
| **Settings**     | **NONE**                                             | ❌**Gap**   |
| **User CRUD**    | **NONE**                                             | ❌**Gap**   |
| Sales/Purchasing | order lifecycle events                               | ✅          |
| System           | integrity_check                                      | ✅          |

### 3.3 Critical Bug — Archive Checksum Mismatch


| #    | Severity | Finding                                                                             | Evidence             |
| ---- | -------- | ----------------------------------------------------------------------------------- | -------------------- |
| F-14 | **HIGH** | `ArchiveActivityLogs` checksum verification payload doesn't match `AuditLogService` | See comparison below |

**`AuditLogService::computeChecksum()`** — AuditLogService.php:

```php
json_encode([$user_id, $module, $action, $description, $ip_address, $created_at,
             $old_data, $new_data])  // ✅ 8 fields
```

**`ArchiveActivityLogs` verification** — ArchiveActivityLogs.php:

```php
json_encode([$user_id, $module, $action, $description, $ip_address, $created_at])
// ❌ 6 fields — MISSING old_data, new_data
```

**Impact:** Any audit log record that has `old_data` or `new_data` (i.e., all update/delete records from Phase 5+) will be **falsely flagged as having an invalid checksum** during archiving. The archive's `$verified` count will be wrong — integrity reports will show phantom tampering alerts.

### 3.4 Additional Findings


| #    | Severity   | Finding                                         | Evidence                                                                                                                                                                                 |
| ---- | ---------- | ----------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| F-15 | **HIGH**   | No audit logging on User CRUD operations        | UserController.php —`store()`, `update()`, `destroy()` have zero `AuditLogService::log()` calls. Role changes, permission modifications, user creation/deletion completely untraceable. |
| F-16 | **MEDIUM** | DB-level admin can bypass Eloquent immutability | `updating`/`deleting` hooks only protect via Eloquent ORM. Direct SQL (`UPDATE activity_logs SET ...`) bypasses this. Mitigated by: HMAC verification detects the change after the fact. |
| F-17 | **INFO**   | HMAC secret tied to APP_KEY                     | If APP_KEY rotates, all existing checksums become invalid. Consider a dedicated`AUDIT_HMAC_KEY`.                                                                                         |

---

## PILLAR 4 — Backup, Maintenance & Error Handling

### 4.1 Backup Schedule


| Schedule      | Task                                  | Config                                 |
| ------------- | ------------------------------------- | -------------------------------------- |
| Daily 02:00   | Full backup (files + DB)              | `backup:run`                           |
| Every 6 hours | DB-only backup                        | `backup:run --only-db`                 |
| Sunday 04:00  | Log archive (>12 months) + CSV export | `log:archive --months=12 --export-csv` |
| Daily 03:00   | Cleanup old backups                   | `backup:clean`                         |
| Daily 06:00   | Monitor backup health                 | `backup:monitor`                       |

**Assessment:** Schedule is solid. Frequency appropriate for ERP. CSV export ensures human-readable archive ✅

### 4.2 Backup Security


| #    | Severity   | Finding                                                   | Evidence                                                                                                                                                                                                                                                        |
| ---- | ---------- | --------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| F-18 | **HIGH**   | Path traversal risk in backup download                    | SystemMaintenanceController.php:`$filename = $request->query('file')` passed directly to `$disk->exists($filename)` and `$disk->download($filename)`. A crafted request like .env could download the environment file if the backup disk root allows traversal. |
| F-19 | **HIGH**   | .env.example ships with`APP_DEBUG=true`                   | Stack traces with full file paths, database credentials, and environment variables will be exposed if this value is copied to production                                                                                                                        |
| F-20 | **MEDIUM** | Error messages in backup/archive expose exception details | `$e->getMessage()` passed directly to session flash messages in `runBackup()` and `runArchive()` — could leak internal paths or SQL errors to the admin UI                                                                                                     |
| F-21 | **LOW**    | No backup encryption at rest                              | `spatie/laravel-backup` supports encryption but it's not configured. Backup ZIP files contain raw database dumps.                                                                                                                                               |
| F-22 | **LOW**    | Archive CSV directory permissions                         | `mkdir($dir, 0750, true)` in `ArchiveActivityLogs` — good default, but no chown/group enforcement                                                                                                                                                              |

### 4.3 Maintenance Dashboard

**Positive:** Dashboard provides disk usage, recent backups (last 5), total backup size, archive/main log counts. Well-structured for operational visibility.

---

## PILLAR 5 — SWOT Analysis

### Strengths 💪


| #   | Strength                                              | Evidence                                                   |
| --- | ----------------------------------------------------- | ---------------------------------------------------------- |
| S1  | **ITGC-compliant password policy**                    | `uncompromised()` + mixed case + symbols + 90-day rotation |
| S2  | **Defense-in-depth middleware chain**                 | 10 middleware, correct execution order                     |
| S3  | **HMAC tamper detection with timing-safe comparison** | SHA-256 +`hash_equals()`                                   |
| S4  | **Eloquent-level audit immutability**                 | Model hooks block update/delete                            |
| S5  | **Comprehensive auth event logging**                  | 5 event types via subscriber pattern                       |
| S6  | **Session serialization = JSON**                      | Mitigates PHP gadget-chain attacks                         |
| S7  | **Granular 52-permission RBAC**                       | Module-action matrix with validation                       |
| S8  | **Fiscal period locking**                             | `EnsureOpenFiscalPeriod` prevents backdated entries        |
| S9  | **Strong security headers**                           | CSP, HSTS, X-Frame, Permissions-Policy                     |
| S10 | **Self-auditing integrity checks**                    | Verification action itself is logged                       |
| S11 | **Phase 5 old_data/new_data in checksums**            | Field-level change tracking in HMAC                        |
| S12 | **No public registration**                            | Explicitly disabled with comment in route file             |
| S13 | **Mid-session deactivation enforcement**              | Active user check on every request                         |
| S14 | **JSON session serialization**                        | Prevents object injection attacks                          |

### Weaknesses ⚠️


| #  | Weakness                                 | Risk                                           | Priority |
| -- | ---------------------------------------- | ---------------------------------------------- | -------- |
| W1 | Archive checksum mismatch (F-14)         | False tampering alerts, broken integrity chain | **P0**   |
| W2 | Zero audit trail on Settings (F-10)      | Tax/currency changes untraceable — audit fail | **P0**   |
| W3 | Zero audit trail on User CRUD (F-15)     | Role escalation undetectable                   | **P0**   |
| W4 | Path traversal in backup download (F-18) | Potential .env exfiltration                    | **P1**   |
| W5 | `APP_DEBUG=true` in .env.example (F-19)  | Production stack trace exposure                | **P1**   |
| W6 | Session encryption disabled (F-04)       | Cookie content readable if intercepted         | **P2**   |
| W7 | No 2FA rate limiting (F-07)              | TOTP brute-force possible                      | **P2**   |
| W8 | Hardcoded`id === 1` (F-02)               | Fragile, breaks on DB migration/reseeding      | **P3**   |

### Opportunities 🚀


| #  | Opportunity                                    | Impact                                                               |
| -- | ---------------------------------------------- | -------------------------------------------------------------------- |
| O1 | **Settings versioning table**                  | Full rollback + before/after comparison for compliance               |
| O2 | **Mandatory 2FA for admins**                   | Enforce 2FA on admin role at middleware level                        |
| O3 | **Dedicated `AUDIT_HMAC_KEY`**                 | Decouple audit integrity from APP_KEY rotation                       |
| O4 | **API rate limiter setup**                     | `RateLimiter::for('api', ...)` — essential before mobile API launch |
| O5 | **BPJS rates as settings**                     | Move from PHP constants to admin-configurable settings               |
| O6 | **Backup encryption at rest**                  | `spatie/laravel-backup` supports it natively                         |
| O7 | **Permission split: `hr` → `hr` + `payroll`** | Separation of duties for salary data                                 |
| O8 | **IP whitelisting for admin routes**           | Additional layer before mobile API exposure                          |

### Threats 🔴


| #  | Threat                                                | Likelihood                 | Impact                            |
| -- | ----------------------------------------------------- | -------------------------- | --------------------------------- |
| T1 | Archive falsely flags post-Phase5 records as tampered | **Certain**                | Loss of trust in integrity system |
| T2 | Settings tampering goes undetected (no audit trail)   | High                       | Compliance failure                |
| T3 | User role escalation undetected (no audit trail)      | High                       | Privilege abuse                   |
| T4 | Backup path traversal → credential theft             | Medium                     | Full system compromise            |
| T5 | APP_DEBUG left on in production                       | Medium                     | Information disclosure            |
| T6 | APP_KEY rotation invalidates all checksums            | Low                        | False integrity alarms            |
| T7 | No global API rate limiter for upcoming mobile API    | **Certain** (if not added) | DDoS / credential stuffing        |

---

## Priority Remediation Roadmap


| Priority | Finding                         | Fix                                                                                               |
| -------- | ------------------------------- | ------------------------------------------------------------------------------------------------- |
| **P0**   | F-14: Archive checksum mismatch | Add`$row->old_data`, `$row->new_data` to payload in ArchiveActivityLogs.php L61-67                |
| **P0**   | F-10: Settings audit gap        | Add`AuditLogService::log()` with old/new data to `SettingsController::update()`                   |
| **P0**   | F-15: User CRUD audit gap       | Add`AuditLogService::log()` to `UserController::store/update/destroy`                             |
| **P1**   | F-18: Path traversal            | Validate`$filename` against `basename()` or restrict to backup disk root with `Str::startsWith()` |
| **P1**   | F-19: APP_DEBUG                 | Change .env.example to`APP_DEBUG=false`                                                           |
| **P1**   | F-04/F-05: Session security     | Set`SESSION_ENCRYPT=true`, add `SESSION_SECURE_COOKIE=true` to .env.example                       |
| **P2**   | F-07: 2FA rate limit            | Add throttle middleware to 2FA challenge route                                                    |
| **P2**   | F-20: Error message exposure    | Wrap`$e->getMessage()` in `app()->isProduction() ? __('generic_error') : $e->getMessage()`        |
| **P3**   | F-02: Hardcoded ID              | Replace`$user->id === 1` with `$user->is_primary_admin` flag or role check                        |
| **P3**   | F-08: Dead code                 | Delete`RegisteredUserController.php`                                                              |

---

**Total Findings:** 22 (3 HIGH, 7 MEDIUM, 6 LOW, 3 INFO, 3 Positive categories)
**Verdict:** ManERP has a **strong security foundation** — HMAC integrity, defense-in-depth middleware, ITGC-compliant auth, and immutable audit logs are all well-implemented. However, **three P0 gaps must be patched before mobile API exposure**: the archive checksum mismatch, and the missing audit trails on Settings and User CRUD. The path traversal risk (P1) should also be closed before any public-facing endpoint is added.

Shall I proceed with implementing the P0/P1 fixes?
