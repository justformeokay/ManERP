<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Tests\TestCase;

/**
 * Phase 6 — Security Perimeter Hardening Tests
 *
 * Verifies: path traversal protection, audit trail on user/settings CRUD,
 * audit log immutability, archive checksum integrity, and security headers.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        // Simplify password validation for controller tests (avoid external API calls)
        Password::defaults(fn () => Password::min(8));

        $this->admin = User::factory()->create([
            'role'   => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->staff = User::factory()->create([
            'role'        => User::ROLE_STAFF,
            'status'      => User::STATUS_ACTIVE,
            'permissions' => ['sales.view', 'sales.create'],
        ]);
    }

    // ─── PATH TRAVERSAL PROTECTION ───────────────────────────────

    public function test_path_traversal_is_blocked(): void
    {
        // Attempt to download .env via path traversal
        $response = $this->actingAs($this->admin)
            ->get(route('maintenance.download', ['file' => '../../.env']));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_path_traversal_double_encoded_is_blocked(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('maintenance.download', ['file' => '..%2F..%2F.env']));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_absolute_path_is_blocked(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('maintenance.download', ['file' => '/etc/passwd']));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_non_zip_file_download_is_blocked(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('maintenance.download', ['file' => 'ManERP/backup.sql']));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_backup_outside_app_dir_is_blocked(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('maintenance.download', ['file' => 'other-folder/backup.zip']));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ─── USER PERMISSION CHANGE IS LOGGED ────────────────────────

    public function test_user_creation_is_logged(): void
    {
        $this->actingAs($this->admin)->post(route('settings.users.store'), [
            'name'                  => 'New User',
            'email'                 => 'newuser@test.com',
            'password'              => 'P@ssw0rd!Str0ng',
            'password_confirmation' => 'P@ssw0rd!Str0ng',
            'role'                  => User::ROLE_STAFF,
            'phone'                 => '08123456789',
            'status'                => User::STATUS_ACTIVE,
            'permissions'           => ['sales.view', 'sales.create'],
        ]);

        $log = ActivityLog::where('module', 'users')
            ->where('action', 'create')
            ->latest('id')
            ->first();

        $this->assertNotNull($log, 'User creation must be logged');
        $this->assertStringContains('New User', $log->description);
        $this->assertNotNull($log->checksum, 'Audit log must have HMAC checksum');
        $this->assertNotNull($log->new_data);
        $this->assertEquals('sales.view', $log->new_data['permissions'][0]);
    }

    public function test_user_permission_change_is_logged(): void
    {
        $target = User::factory()->create([
            'role'        => User::ROLE_STAFF,
            'status'      => User::STATUS_ACTIVE,
            'permissions' => ['sales.view'],
        ]);

        $this->actingAs($this->admin)->put(route('settings.users.update', $target), [
            'name'        => $target->name,
            'email'       => $target->email,
            'role'        => User::ROLE_STAFF,
            'phone'       => $target->phone,
            'status'      => User::STATUS_ACTIVE,
            'permissions' => ['sales.view', 'sales.create', 'hr.view'],
        ]);

        $log = ActivityLog::where('module', 'users')
            ->where('action', 'update')
            ->where('auditable_id', $target->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log, 'User permission change must be logged');
        $this->assertEquals(['sales.view'], $log->old_data['permissions']);
        $this->assertEquals(['sales.view', 'sales.create', 'hr.view'], $log->new_data['permissions']);
        $this->assertNotNull($log->checksum);
        $this->assertTrue(AuditLogService::verifyChecksum($log), 'Checksum must be valid');
    }

    public function test_user_role_change_is_logged(): void
    {
        $target = User::factory()->create([
            'role'        => User::ROLE_STAFF,
            'status'      => User::STATUS_ACTIVE,
            'permissions' => ['sales.view'],
        ]);

        $this->actingAs($this->admin)->put(route('settings.users.update', $target), [
            'name'   => $target->name,
            'email'  => $target->email,
            'role'   => User::ROLE_ADMIN,
            'phone'  => $target->phone,
            'status' => User::STATUS_ACTIVE,
        ]);

        $log = ActivityLog::where('module', 'users')
            ->where('action', 'update')
            ->where('auditable_id', $target->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals(User::ROLE_STAFF, $log->old_data['role']);
        $this->assertEquals(User::ROLE_ADMIN, $log->new_data['role']);
    }

    public function test_user_deletion_is_logged(): void
    {
        $target = User::factory()->create([
            'role'   => User::ROLE_STAFF,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->admin)->delete(route('settings.users.destroy', $target));

        $log = ActivityLog::where('module', 'users')
            ->where('action', 'delete')
            ->latest('id')
            ->first();

        $this->assertNotNull($log, 'User deletion must be logged');
        $this->assertNotNull($log->old_data, 'Deleted user data must be preserved');
        $this->assertNull($log->new_data);
        $this->assertNotNull($log->checksum);
    }

    public function test_last_admin_cannot_be_deleted(): void
    {
        // Only one admin exists
        $this->assertEquals(1, User::where('role', User::ROLE_ADMIN)->count());

        // Admin tries to delete themselves — blocked by self-delete check
        $response = $this->actingAs($this->admin)
            ->delete(route('settings.users.destroy', $this->admin));
        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    // ─── SETTINGS AUDIT TRAIL ─────────────────────────────────────

    public function test_settings_change_is_logged(): void
    {
        Setting::set('default_tax_rate', '11');

        $this->actingAs($this->admin)->post(route('settings.update'), [
            'company_name'          => 'Test Corp',
            'company_email'         => 'test@corp.com',
            'company_phone'         => '021-123',
            'company_address'       => 'Jakarta',
            'default_currency'      => 'IDR',
            'timezone'              => 'Asia/Jakarta',
            'default_payment_terms' => 30,
            'default_tax_rate'      => 12,
            'low_stock_threshold'   => 5,
            'items_per_page'        => 25,
        ]);

        $log = ActivityLog::where('module', 'settings')
            ->where('action', 'update')
            ->latest('id')
            ->first();

        $this->assertNotNull($log, 'Settings change must be logged');
        $this->assertEquals('11', $log->old_data['default_tax_rate']);
        $this->assertEquals('12', $log->new_data['default_tax_rate']);
        $this->assertNotNull($log->checksum);
        $this->assertTrue(AuditLogService::verifyChecksum($log));
    }

    // ─── AUDIT LOG IMMUTABILITY ──────────────────────────────────

    public function test_unauthorized_audit_log_deletion_is_blocked(): void
    {
        $log = AuditLogService::log('system', 'test', 'Immutability test');

        // Attempt to delete via Eloquent — must throw RuntimeException
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('immutable');
        $log->delete();
    }

    public function test_audit_log_update_is_blocked(): void
    {
        $log = AuditLogService::log('system', 'test', 'Immutability update test');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('immutable');
        $log->update(['description' => 'tampered']);
    }

    public function test_audit_log_checksum_detects_tampering(): void
    {
        $log = AuditLogService::log('users', 'create', 'Created test user');

        $this->assertTrue(AuditLogService::verifyChecksum($log));

        // Tamper directly via DB (bypass Eloquent hooks)
        DB::table('activity_logs')
            ->where('id', $log->id)
            ->update(['description' => 'TAMPERED']);

        $log->refresh();
        $this->assertFalse(AuditLogService::verifyChecksum($log));
    }

    // ─── ARCHIVE CHECKSUM INTEGRITY ──────────────────────────────

    public function test_archive_checksum_matches_audit_service(): void
    {
        // Create a log with old_data/new_data (Phase 5 format)
        $log = AuditLogService::log(
            'users',
            'update',
            'Permission change test',
            ['role' => 'staff', 'permissions' => ['sales.view']],
            ['role' => 'staff', 'permissions' => ['sales.view', 'hr.view']],
        );

        $this->assertTrue(AuditLogService::verifyChecksum($log));

        // Simulate archive verification logic (same as ArchiveActivityLogs command)
        $row = DB::table('activity_logs')->where('id', $log->id)->first();

        $payload = json_encode([
            $row->user_id,
            $row->module,
            $row->action,
            $row->description,
            $row->ip_address,
            Carbon::parse($row->created_at)->toIso8601String(),
            json_decode($row->old_data, true),
            json_decode($row->new_data, true),
        ]);
        $archiveChecksum = hash_hmac('sha256', $payload, config('app.key'));

        $this->assertTrue(
            hash_equals($row->checksum, $archiveChecksum),
            'Archive checksum calculation must match AuditLogService checksum'
        );
    }

    public function test_archive_checksum_valid_for_records_without_data(): void
    {
        // Create a simple log without old_data/new_data
        $log = AuditLogService::log('system', 'integrity_check', 'Simple test');

        $row = DB::table('activity_logs')->where('id', $log->id)->first();

        $payload = json_encode([
            $row->user_id,
            $row->module,
            $row->action,
            $row->description,
            $row->ip_address,
            Carbon::parse($row->created_at)->toIso8601String(),
            json_decode($row->old_data, true),
            json_decode($row->new_data, true),
        ]);
        $archiveChecksum = hash_hmac('sha256', $payload, config('app.key'));

        $this->assertTrue(hash_equals($row->checksum, $archiveChecksum));
    }

    // ─── SECURITY HEADERS ────────────────────────────────────────

    public function test_security_headers_are_present(): void
    {
        $response = $this->actingAs($this->admin)->get(route('maintenance.index'));

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_csp_header_present(): void
    {
        $response = $this->actingAs($this->admin)->get(route('maintenance.index'));

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'self'", $csp);
    }

    // ─── DEAD CODE REMOVAL ───────────────────────────────────────

    public function test_registration_controller_removed(): void
    {
        $this->assertFileDoesNotExist(
            app_path('Http/Controllers/Auth/RegisteredUserController.php'),
            'RegisteredUserController should be removed (dead code)'
        );
    }

    // ─── HELPER ──────────────────────────────────────────────────

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertStringContainsString($needle, $haystack);
    }
}
