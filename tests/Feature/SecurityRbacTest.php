<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TUGAS 1: Security & RBAC Void Test
 *
 * Ensures Staff users cannot access admin-only routes.
 * Ensures Admin users CAN access those same routes (positive control).
 */
class SecurityRbacTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'   => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->staff = User::factory()->create([
            'role'        => User::ROLE_STAFF,
            'status'      => User::STATUS_ACTIVE,
            'permissions' => ['sales.view', 'sales.create'], // no accounting/admin perms
        ]);
    }

    // ─── MAINTENANCE (Admin-only) ────────────────────────────────

    public function test_staff_cannot_access_maintenance_index(): void
    {
        $response = $this->actingAs($this->staff)->get(route('maintenance.index'));

        $response->assertForbidden();
        $this->assertTrue($this->staff->isStaff(), 'User must be staff role');
        $this->assertFalse($this->staff->isAdmin(), 'Staff must NOT be admin');
    }

    public function test_admin_can_access_maintenance_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('maintenance.index'));

        $response->assertSuccessful();
        $this->assertTrue($this->admin->isAdmin());
        $this->assertTrue($this->admin->hasPermission('accounting.view'), 'Admin should have all permissions');
    }

    public function test_staff_cannot_run_backup(): void
    {
        $response = $this->actingAs($this->staff)->post(route('maintenance.backup'), [
            'type' => 'db',
        ]);

        $response->assertForbidden();
        $this->assertFalse($this->staff->isAdmin());
        $this->assertTrue($this->staff->isStaff());
    }

    public function test_staff_cannot_run_archive(): void
    {
        $response = $this->actingAs($this->staff)->post(route('maintenance.archive'));

        $response->assertForbidden();
        $this->assertFalse($this->staff->isAdmin());
        $this->assertTrue($this->staff->isActive(), 'User must still be active');
    }

    // ─── JOURNAL ENTRIES (Permission-gated) ──────────────────────

    public function test_staff_without_accounting_permission_cannot_access_journals(): void
    {
        $response = $this->actingAs($this->staff)->get(route('accounting.journals.index'));

        $response->assertForbidden();
        $this->assertFalse($this->staff->hasPermission('accounting.view'));
        $this->assertFalse($this->staff->hasModuleAccess('accounting'));
    }

    public function test_staff_with_accounting_permission_can_access_journals(): void
    {
        $staffWithAccounting = User::factory()->create([
            'role'        => User::ROLE_STAFF,
            'status'      => User::STATUS_ACTIVE,
            'permissions' => ['accounting.view', 'accounting.create'],
        ]);

        $response = $this->actingAs($staffWithAccounting)->get(route('accounting.journals.index'));

        $response->assertSuccessful();
        $this->assertTrue($staffWithAccounting->hasPermission('accounting.view'));
        $this->assertTrue($staffWithAccounting->hasModuleAccess('accounting'));
    }

    public function test_staff_cannot_create_journal_without_create_permission(): void
    {
        $staffViewOnly = User::factory()->create([
            'role'        => User::ROLE_STAFF,
            'status'      => User::STATUS_ACTIVE,
            'permissions' => ['accounting.view'],
        ]);

        $response = $this->actingAs($staffViewOnly)->get(route('accounting.journals.create'));

        $response->assertForbidden();
        $this->assertTrue($staffViewOnly->hasPermission('accounting.view'));
        $this->assertFalse($staffViewOnly->hasPermission('accounting.create'));
    }

    // ─── AUDIT LOGS (Admin-only) ─────────────────────────────────

    public function test_staff_cannot_access_audit_logs(): void
    {
        $response = $this->actingAs($this->staff)->get(route('audit-logs.index'));

        $response->assertForbidden();
        $this->assertFalse($this->staff->isAdmin());
        $this->assertTrue($this->staff->isStaff());
    }

    public function test_admin_can_access_audit_logs(): void
    {
        $response = $this->actingAs($this->admin)->get(route('audit-logs.index'));

        $response->assertSuccessful();
        $this->assertTrue($this->admin->isAdmin());
        $this->assertTrue($this->admin->hasPermission('reports.view'));
    }

    public function test_staff_cannot_verify_audit_integrity(): void
    {
        $response = $this->actingAs($this->staff)->get(route('audit-logs.verify-integrity'));

        $response->assertForbidden();
        $this->assertFalse($this->staff->isAdmin());
        $this->assertTrue($this->staff->isActive());
    }

    // ─── SETTINGS & USER MANAGEMENT (Admin-only) ─────────────────

    public function test_staff_cannot_access_settings(): void
    {
        $response = $this->actingAs($this->staff)->get(route('settings.index'));

        $response->assertForbidden();
        $this->assertTrue($this->staff->isStaff());
        $this->assertFalse($this->staff->isAdmin());
    }

    public function test_staff_cannot_access_user_management(): void
    {
        $response = $this->actingAs($this->staff)->get(route('settings.users.index'));

        $response->assertForbidden();
        $this->assertTrue($this->staff->isStaff());
        $this->assertFalse($this->staff->isAdmin());
    }

    // ─── UNAUTHENTICATED ACCESS ──────────────────────────────────

    public function test_guest_is_redirected_to_login_on_admin_routes(): void
    {
        $response = $this->get(route('maintenance.index'));
        $response->assertRedirect(route('login'));

        $response2 = $this->get(route('audit-logs.index'));
        $response2->assertRedirect(route('login'));

        $response3 = $this->get(route('settings.index'));
        $response3->assertRedirect(route('login'));
    }
}
