<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    // ── TUGAS 5: Profile page is accessible & NOT blank ─────

    public function test_profile_page_is_accessible(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertSee($user->name);
        $response->assertSee($user->email);
        $response->assertSee(__('messages.profile_my_permissions'));
        $response->assertSee(__('messages.profile_security_center'));
        $response->assertSee(__('messages.profile_recent_activity'));
    }

    public function test_profile_page_shows_staff_permissions(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'permissions' => ['clients.view', 'clients.create', 'sales.view'],
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertSee('CRM / Clients');
        $response->assertSee('Sales Orders');
        $response->assertDontSee(__('messages.profile_admin_full_access'));
    }

    public function test_profile_page_shows_admin_full_access_badge(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertSee(__('messages.profile_admin_full_access'));
    }

    // ── TUGAS 5: Update profile ─────────────────────────────

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_user_cannot_update_email_to_existing_one(): void
    {
        $existingUser = User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['email' => 'original@example.com']);

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => 'taken@example.com',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertSame('original@example.com', $user->fresh()->email);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/profile');
        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    // ── TUGAS 5: Password change ────────────────────────────

    public function test_user_can_update_password_with_valid_current_password(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/password', [
            'current_password' => 'password',
            'password' => 'N3w_Str0ng!Pass',
            'password_confirmation' => 'N3w_Str0ng!Pass',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('N3w_Str0ng!Pass', $user->fresh()->password));
    }

    public function test_user_cannot_change_password_with_wrong_current_password(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/password', [
            'current_password' => 'wrong-password',
            'password' => 'N3w_Str0ng!Pass',
            'password_confirmation' => 'N3w_Str0ng!Pass',
        ]);

        $response->assertSessionHasErrors('current_password', null, 'updatePassword');
    }

    // ── TUGAS 5: Impersonation banner ───────────────────────

    public function test_impersonation_banner_is_visible_during_session(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Boss']);
        $staff = User::factory()->create(['role' => 'staff', 'permissions' => ['clients.view']]);

        $response = $this
            ->actingAs($staff)
            ->withSession(['impersonator_id' => $admin->id])
            ->get('/profile');

        $response->assertOk();
        $response->assertSee(__('messages.rbac_stop_impersonation'));
    }

    // ── TUGAS 5: Account deletion ───────────────────────────

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete('/profile', [
            'password' => 'password',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect('/');
        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete('/profile', [
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('password', null, 'userDeletion');
        $this->assertNotNull($user->fresh());
    }

    // ── TUGAS 5: Profile update is audited ──────────────────

    public function test_profile_update_creates_audit_log(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        $this->actingAs($user)->patch('/profile', [
            'name' => 'New Name',
            'email' => $user->email,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'module' => 'users',
            'action' => 'update',
        ]);
    }

    // ── TUGAS 5: Danger zone hidden during impersonation ────

    public function test_delete_account_section_hidden_during_impersonation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff', 'permissions' => ['clients.view']]);

        $response = $this
            ->actingAs($staff)
            ->withSession(['impersonator_id' => $admin->id])
            ->get('/profile');

        $response->assertOk();
        $response->assertDontSee(__('messages.profile_danger_zone'));
    }
}
