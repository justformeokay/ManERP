<?php

namespace Tests\Feature;

use App\Models\SystemLicense;
use App\Models\User;
use App\Services\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseGuardTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function createActiveLicense(array $overrides = []): SystemLicense
    {
        return SystemLicense::create(array_merge([
            'license_key' => 'TEST-LICENSE-KEY',
            'license_type' => SystemLicense::TYPE_SUBSCRIPTION,
            'plan_name' => 'Professional',
            'user_limit' => 5,
            'features_enabled' => ['sales', 'purchasing', 'inventory'],
            'starts_at' => now()->subDays(30),
            'expires_at' => now()->addDays(30),
            'is_active' => true,
        ], $overrides));
    }

    // ─── TEST 1: User cannot be created beyond license limit ───

    public function test_user_cannot_be_created_beyond_limit(): void
    {
        $admin = $this->createAdmin();

        // Create license with user_limit = 2
        $this->createActiveLicense(['user_limit' => 2]);

        // Admin is already 1 active user, create 1 more to reach limit
        User::factory()->create(['status' => User::STATUS_ACTIVE]);

        LicenseService::clearCache();

        // Now try to create a 3rd user — should be blocked by middleware
        $response = $this->actingAs($admin)->post(route('settings.users.store'), [
            'name' => 'Over Limit',
            'email' => 'overlimit@example.com',
            'password' => 'Xk9$mNvQ2pLw!rT7',
            'password_confirmation' => 'Xk9$mNvQ2pLw!rT7',
            'role' => User::ROLE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'permissions' => ['sales.view'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('users', ['email' => 'overlimit@example.com']);
    }

    public function test_user_can_be_created_within_limit(): void
    {
        $admin = $this->createAdmin();

        // Create license with user_limit = 5
        $this->createActiveLicense(['user_limit' => 5]);

        LicenseService::clearCache();

        $response = $this->actingAs($admin)->post(route('settings.users.store'), [
            'name' => 'Within Limit',
            'email' => 'withinlimit@example.com',
            'password' => 'Xk9$mNvQ2pLw!rT7',
            'password_confirmation' => 'Xk9$mNvQ2pLw!rT7',
            'role' => User::ROLE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'permissions' => ['sales.view'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'withinlimit@example.com']);
    }

    // ─── TEST 2: Access denied when subscription expired ───

    public function test_access_denied_when_subscription_expired(): void
    {
        $admin = $this->createAdmin();

        // Create an expired license (past grace period)
        $this->createActiveLicense([
            'expires_at' => now()->subDays(10), // 10 days ago, past 3-day grace
        ]);

        LicenseService::clearCache();

        $response = $this->actingAs($admin)->get(route('notifications.index'));

        $response->assertRedirect(route('license.expired'));
    }

    public function test_access_allowed_during_grace_period(): void
    {
        $admin = $this->createAdmin();

        // Expired 1 day ago — within 3-day grace period
        $this->createActiveLicense([
            'expires_at' => now()->subDays(1),
        ]);

        LicenseService::clearCache();

        $response = $this->actingAs($admin)->get(route('notifications.index'));

        // Should NOT redirect to expired page (grace period active)
        $response->assertOk();
    }

    public function test_grace_period_warning_shown(): void
    {
        $admin = $this->createAdmin();

        // Expired 1 day ago — within grace period
        $this->createActiveLicense([
            'expires_at' => now()->subDays(1),
        ]);

        LicenseService::clearCache();

        $response = $this->actingAs($admin)->get(route('notifications.index'));

        $response->assertSessionHas('license_warning');
    }

    public function test_lifetime_license_never_expires(): void
    {
        $admin = $this->createAdmin();

        $this->createActiveLicense([
            'license_type' => SystemLicense::TYPE_LIFETIME,
            'expires_at' => null,
        ]);

        LicenseService::clearCache();

        $response = $this->actingAs($admin)->get(route('notifications.index'));
        $response->assertOk();
    }

    // ─── TEST 3: Lifetime license key activation ───

    public function test_lifetime_license_key_activation_works(): void
    {
        $admin = $this->createAdmin();
        $companyName = 'PT Test Company';
        $domain = 'erp.testcompany.com';

        // Generate the expected serial number
        $serialNumber = LicenseService::generateSerialNumber($companyName, $domain);

        // Create an active license that needs activation
        $this->createActiveLicense([
            'license_type' => SystemLicense::TYPE_LIFETIME,
            'expires_at' => null,
        ]);

        LicenseService::clearCache();

        $response = $this->actingAs($admin)->post(route('license.processActivation'), [
            'serial_number' => $serialNumber,
            'company_name' => $companyName,
            'domain' => $domain,
        ]);

        $response->assertRedirect(route('license.index'));
        $response->assertSessionHas('success');

        // Verify license was updated
        $license = SystemLicense::where('is_active', true)->first();
        $this->assertEquals($companyName, $license->company_name);
        $this->assertEquals($domain, $license->domain);
        $this->assertNotNull($license->activated_at);
    }

    public function test_activation_fails_with_invalid_serial(): void
    {
        $admin = $this->createAdmin();

        $this->createActiveLicense();

        LicenseService::clearCache();

        $response = $this->actingAs($admin)->post(route('license.processActivation'), [
            'serial_number' => str_repeat('a', 64),
            'company_name' => 'PT Test Company',
            'domain' => 'erp.testcompany.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_license_dashboard_accessible_by_admin(): void
    {
        $admin = $this->createAdmin();

        $this->createActiveLicense();
        LicenseService::clearCache();

        $response = $this->actingAs($admin)->get(route('license.index'));
        $response->assertOk();
        $response->assertViewIs('license.index');
    }

    public function test_license_dashboard_denied_for_staff(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'permissions' => ['sales.view'],
        ]);

        $this->createActiveLicense();
        LicenseService::clearCache();

        $response = $this->actingAs($staff)->get(route('license.index'));
        $response->assertStatus(403);
    }

    public function test_no_license_allows_access(): void
    {
        $admin = $this->createAdmin();

        // No license at all — should allow access (fresh install)
        LicenseService::clearCache();

        $response = $this->actingAs($admin)->get(route('notifications.index'));
        $response->assertOk();
    }

    public function test_license_expired_page_accessible(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('license.expired'));
        $response->assertOk();
    }
}
