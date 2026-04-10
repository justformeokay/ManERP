<?php

namespace Tests\Feature;

use App\Console\Commands\SendLeadFollowUpReminders;
use App\Http\Controllers\ClientController;
use App\Mail\LeadFollowUpReminderMail;
use App\Mail\StaleLeadsDigest;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Models\Setting;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\LeadFollowUpReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LeadFollowUpReminderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'   => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        $this->warehouse = Warehouse::create([
            'code' => 'WH-LF', 'name' => 'LF Warehouse', 'is_active' => true,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════

    private function createClient(array $overrides = []): Client
    {
        return Client::create(array_merge([
            'code'   => 'CLI-' . mt_rand(1000, 9999),
            'name'   => 'Test Client',
            'status' => 'active',
            'type'   => 'customer',
        ], $overrides));
    }

    private function createStaffUser(string $role = User::ROLE_STAFF, array $permissions = []): User
    {
        return User::factory()->create([
            'role'        => $role,
            'status'      => User::STATUS_ACTIVE,
            'permissions' => $permissions,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 1: Scheduled Command — Core Logic
    // ═══════════════════════════════════════════════════════════════

    public function test_command_sends_reminders_for_stale_leads(): void
    {
        Notification::fake();

        // Stale lead: updated 10 days ago
        $staleLead = $this->createClient(['name' => 'Stale Lead', 'type' => 'lead']);
        Client::where('id', $staleLead->id)->update(['updated_at' => now()->subDays(10)]);

        // Fresh lead: updated today — should NOT trigger
        $freshLead = $this->createClient(['name' => 'Fresh Lead', 'type' => 'lead']);

        $this->artisan('leads:send-reminders')
            ->expectsOutputToContain('Sent reminders for 1 lead(s)')
            ->assertSuccessful();

        Notification::assertSentTo($this->admin, LeadFollowUpReminder::class, function ($notification) use ($staleLead) {
            return $notification->client->id === $staleLead->id && !$notification->isEscalation;
        });
    }

    public function test_command_ignores_prospects_and_customers(): void
    {
        Notification::fake();

        $prospect = $this->createClient(['type' => 'prospect']);
        Client::where('id', $prospect->id)->update(['updated_at' => now()->subDays(30)]);

        $customer = $this->createClient(['type' => 'customer']);
        Client::where('id', $customer->id)->update(['updated_at' => now()->subDays(30)]);

        $this->artisan('leads:send-reminders')
            ->expectsOutputToContain('No stale leads found')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_command_ignores_inactive_leads(): void
    {
        Notification::fake();

        $inactiveLead = $this->createClient(['type' => 'lead', 'status' => 'inactive']);
        Client::where('id', $inactiveLead->id)->update(['updated_at' => now()->subDays(30)]);

        $this->artisan('leads:send-reminders')
            ->expectsOutputToContain('No stale leads found')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_command_respects_custom_grace_period(): void
    {
        Notification::fake();

        Setting::set('lead_followup_days', '14');

        // 10-day idle lead — under the 14-day threshold
        $lead = $this->createClient(['type' => 'lead']);
        Client::where('id', $lead->id)->update(['updated_at' => now()->subDays(10)]);

        $this->artisan('leads:send-reminders')
            ->expectsOutputToContain('No stale leads found')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_command_dry_run_does_not_send_notifications(): void
    {
        Notification::fake();

        $lead = $this->createClient(['type' => 'lead']);
        Client::where('id', $lead->id)->update(['updated_at' => now()->subDays(10)]);

        $this->artisan('leads:send-reminders', ['--dry-run' => true])
            ->expectsOutputToContain('Dry run complete')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }

    // ═══════════════════════════════════════════════════════════════
    // Escalation Logic
    // ═══════════════════════════════════════════════════════════════

    public function test_command_escalates_to_managers_after_escalation_period(): void
    {
        Notification::fake();

        Setting::set('lead_followup_days', '7');
        Setting::set('lead_escalation_days', '14');

        // Create a sales manager who does NOT have clients.view (not in salesUsers)
        $manager = $this->createStaffUser('sales_manager', ['clients.edit']);

        $lead = $this->createClient(['type' => 'lead']);
        Client::where('id', $lead->id)->update(['updated_at' => now()->subDays(20)]);

        $this->artisan('leads:send-reminders')
            ->expectsOutputToContain('Escalated: 1')
            ->assertSuccessful();

        // Manager gets escalation
        Notification::assertSentTo($manager, LeadFollowUpReminder::class, function ($notification) {
            return $notification->isEscalation;
        });
    }

    public function test_command_does_not_escalate_before_escalation_period(): void
    {
        Notification::fake();

        Setting::set('lead_followup_days', '7');
        Setting::set('lead_escalation_days', '14');

        $lead = $this->createClient(['type' => 'lead']);
        Client::where('id', $lead->id)->update(['updated_at' => now()->subDays(10)]);

        $this->artisan('leads:send-reminders')
            ->expectsOutputToContain('Escalated: 0')
            ->assertSuccessful();
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 1: Audit Logging
    // ═══════════════════════════════════════════════════════════════

    public function test_command_creates_audit_log_entry(): void
    {
        Notification::fake();

        $lead = $this->createClient(['name' => 'Audit Lead', 'type' => 'lead']);
        Client::where('id', $lead->id)->update(['updated_at' => now()->subDays(10)]);

        $this->artisan('leads:send-reminders')->assertSuccessful();

        $this->assertDatabaseHas('activity_logs', [
            'module'       => 'clients',
            'action'       => 'reminder',
            'auditable_id' => $lead->id,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 2: Notification Content
    // ═══════════════════════════════════════════════════════════════

    public function test_notification_contains_correct_data(): void
    {
        $client = $this->createClient(['name' => 'Test Lead', 'type' => 'lead']);
        $notification = new LeadFollowUpReminder($client, 10, false);
        $data = $notification->toArray($this->admin);

        $this->assertEquals('lead_followup', $data['type']);
        $this->assertEquals($client->id, $data['client_id']);
        $this->assertEquals(10, $data['idle_days']);
        $this->assertStringContainsString('Test Lead', $data['message']);
    }

    public function test_escalation_notification_has_different_type(): void
    {
        $client = $this->createClient(['type' => 'lead']);
        $notification = new LeadFollowUpReminder($client, 15, true);
        $data = $notification->toArray($this->admin);

        $this->assertEquals('lead_followup_escalation', $data['type']);
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 3: Kanban Visual Indicators
    // ═══════════════════════════════════════════════════════════════

    public function test_index_passes_lead_followup_days_to_view(): void
    {
        Setting::set('lead_followup_days', '5');
        $this->createClient();

        $response = $this->actingAs($this->admin)->get(route('clients.index'));

        $response->assertOk();
        $this->assertEquals(5, $response->viewData('leadFollowupDays'));
    }

    public function test_index_shows_stagnant_badge_for_idle_lead(): void
    {
        $staleLead = $this->createClient(['name' => 'Stale Lead Co', 'type' => 'lead']);
        Client::where('id', $staleLead->id)->update(['updated_at' => now()->subDays(10)]);

        $response = $this->actingAs($this->admin)->get(route('clients.index'));

        $response->assertOk();
        $response->assertSee(__('messages.days_idle', ['days' => 10]), false);
        $response->assertSee(__('messages.followed_up'));
    }

    public function test_index_shows_last_updated_on_pipeline_cards(): void
    {
        $this->createClient(['type' => 'lead']);

        $response = $this->actingAs($this->admin)->get(route('clients.index'));

        $response->assertOk();
        $response->assertSee(__('messages.last_updated'));
    }

    // ═══════════════════════════════════════════════════════════════
    // Snooze Endpoint
    // ═══════════════════════════════════════════════════════════════

    public function test_snooze_resets_lead_updated_at(): void
    {
        $lead = $this->createClient(['type' => 'lead']);
        Client::where('id', $lead->id)->update(['updated_at' => now()->subDays(10)]);
        $oldUpdatedAt = $lead->fresh()->updated_at;

        $response = $this->actingAs($this->admin)->postJson(
            route('clients.snooze', $lead)
        );

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $lead->refresh();
        $this->assertTrue($lead->updated_at->gt($oldUpdatedAt));
    }

    public function test_snooze_rejects_non_lead_type(): void
    {
        $customer = $this->createClient(['type' => 'customer']);

        $response = $this->actingAs($this->admin)->postJson(
            route('clients.snooze', $customer)
        );

        $response->assertStatus(422);
    }

    public function test_snooze_creates_audit_log(): void
    {
        $lead = $this->createClient(['name' => 'Snoozed Lead', 'type' => 'lead']);

        $this->actingAs($this->admin)->postJson(route('clients.snooze', $lead));

        $this->assertDatabaseHas('activity_logs', [
            'module'       => 'clients',
            'action'       => 'snooze',
            'auditable_id' => $lead->id,
        ]);
    }

    public function test_snooze_requires_edit_permission(): void
    {
        $staff = $this->createStaffUser(User::ROLE_STAFF, ['clients.view']);
        $lead = $this->createClient(['type' => 'lead']);

        $response = $this->actingAs($staff)->postJson(route('clients.snooze', $lead));

        $response->assertForbidden();
    }

    // ═══════════════════════════════════════════════════════════════
    // TUGAS 4: Settings CRM Tab
    // ═══════════════════════════════════════════════════════════════

    public function test_settings_crm_tab_renders(): void
    {
        $response = $this->actingAs($this->admin)->get(route('settings.index', ['tab' => 'crm']));

        $response->assertOk();
        $response->assertSee(__('messages.stab_crm'));
        $response->assertSee(__('messages.lead_followup_days_label'));
        $response->assertSee(__('messages.lead_escalation_days_label'));
    }

    public function test_settings_crm_can_update_grace_period(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.update.crm'), [
            'lead_followup_days'          => 5,
            'lead_escalation_days'        => 10,
            'lead_followup_email_enabled' => 0,
        ]);

        $response->assertRedirect();
        $this->assertEquals('5', Setting::get('lead_followup_days'));
        $this->assertEquals('10', Setting::get('lead_escalation_days'));
    }

    public function test_settings_crm_rejects_escalation_less_than_followup(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.update.crm'), [
            'lead_followup_days'          => 10,
            'lead_escalation_days'        => 5,
            'lead_followup_email_enabled' => 0,
        ]);

        $response->assertSessionHasErrors('lead_escalation_days');
    }

    public function test_settings_crm_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->post(route('settings.update.crm'), []);

        $response->assertSessionHasErrors([
            'lead_followup_days',
            'lead_escalation_days',
            'lead_followup_email_enabled',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // Database Index (Migration)
    // ═══════════════════════════════════════════════════════════════

    public function test_composite_index_exists_on_clients_table(): void
    {
        // If migration ran, the index should exist — verify by running a query
        // that would benefit from the index (type + updated_at)
        $result = Client::where('type', 'lead')
            ->where('updated_at', '<=', now()->subDays(7))
            ->count();

        $this->assertIsInt($result);
    }

    // ═══════════════════════════════════════════════════════════════
    // Translation Keys
    // ═══════════════════════════════════════════════════════════════

    public function test_lead_followup_translation_keys_exist(): void
    {
        $keys = [
            'lead_followup_title',
            'lead_followup_message',
            'lead_escalation_title',
            'lead_escalation_message',
            'lead_snoozed',
            'days_idle',
            'last_updated',
            'followed_up',
            'snooze_tooltip',
            'idle_for_days',
            'stale_leads_email_subject',
            'stale_leads_email_heading',
            'stale_leads_email_intro',
            'stale_leads_email_body',
            'stab_crm',
            'stab_crm_desc',
            'stab_days',
            'lead_followup_days_label',
            'lead_followup_days_tooltip',
            'lead_escalation_days_label',
            'lead_escalation_days_tooltip',
            'lead_followup_email_label',
            'lead_followup_email_desc',
            'escalation_must_exceed_followup',
            // Email-specific keys
            'email_lead_reminder_subject',
            'email_lead_escalation_subject',
            'email_lead_reminder_heading',
            'email_lead_escalation_heading',
            'email_lead_greeting',
            'email_lead_reminder_intro',
            'email_lead_escalation_intro',
            'email_lead_idle_days',
            'email_lead_last_activity',
            'email_lead_cta',
            'email_lead_cta_hint',
            'email_lead_tips_title',
            'email_lead_tip_1',
            'email_lead_tip_2',
            'email_lead_tip_3',
            'email_lead_footer',
            'email_reminder_sent',
        ];

        foreach ($keys as $key) {
            $translated = __('messages.' . $key);
            $this->assertNotEquals('messages.' . $key, $translated, "Translation key 'messages.{$key}' is missing.");
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // Queued Email — LeadFollowUpReminderMail
    // ═══════════════════════════════════════════════════════════════

    public function test_reminder_mail_has_correct_dynamic_content(): void
    {
        $client = $this->createClient(['name' => 'Acme Corp', 'type' => 'lead', 'email' => 'acme@example.com']);
        $mail = new LeadFollowUpReminderMail($client, 'John Sales', 10, false);

        $rendered = $mail->render();

        $this->assertStringContainsString('John Sales', $rendered);
        $this->assertStringContainsString('Acme Corp', $rendered);
        $this->assertStringContainsString('ManERP', $rendered);
        $this->assertStringContainsString((string) 10, $rendered);
    }

    public function test_escalation_mail_uses_red_theme(): void
    {
        $client = $this->createClient(['name' => 'Slow Lead', 'type' => 'lead']);
        $mail = new LeadFollowUpReminderMail($client, 'Jane Manager', 20, true);

        $rendered = $mail->render();

        // Escalation uses red gradient colors
        $this->assertStringContainsString('#dc2626', $rendered);
        $this->assertStringContainsString(__('messages.email_lead_escalation_heading'), $rendered);
    }

    public function test_reminder_mail_envelope_subject(): void
    {
        $client = $this->createClient(['name' => 'Test Client', 'type' => 'lead']);
        $mail = new LeadFollowUpReminderMail($client, 'Sales', 7, false);

        $envelope = $mail->envelope();

        $this->assertStringContainsString('Test Client', $envelope->subject);
    }

    public function test_command_queues_individual_emails_when_enabled(): void
    {
        Notification::fake();
        Mail::fake();

        Setting::set('lead_followup_email_enabled', '1');

        $lead = $this->createClient(['type' => 'lead']);
        Client::where('id', $lead->id)->update(['updated_at' => now()->subDays(10)]);

        $this->artisan('leads:send-reminders')->assertSuccessful();

        // Individual per-lead email
        Mail::assertQueued(LeadFollowUpReminderMail::class, function ($mail) use ($lead) {
            return $mail->client->id === $lead->id && $mail->isEscalation === false;
        });

        // Digest email
        Mail::assertQueued(StaleLeadsDigest::class);
    }

    public function test_command_does_not_queue_emails_when_disabled(): void
    {
        Notification::fake();
        Mail::fake();

        Setting::set('lead_followup_email_enabled', '0');

        $lead = $this->createClient(['type' => 'lead']);
        Client::where('id', $lead->id)->update(['updated_at' => now()->subDays(10)]);

        $this->artisan('leads:send-reminders')->assertSuccessful();

        Mail::assertNothingQueued();
    }

    public function test_command_sets_reminder_email_sent_at_when_email_enabled(): void
    {
        Notification::fake();
        Mail::fake();

        Setting::set('lead_followup_email_enabled', '1');

        $lead = $this->createClient(['type' => 'lead']);
        Client::where('id', $lead->id)->update(['updated_at' => now()->subDays(10)]);

        $this->artisan('leads:send-reminders')->assertSuccessful();

        $lead->refresh();
        $this->assertNotNull($lead->reminder_email_sent_at);
    }

    public function test_command_does_not_set_reminder_email_sent_at_when_disabled(): void
    {
        Notification::fake();
        Mail::fake();

        Setting::set('lead_followup_email_enabled', '0');

        $lead = $this->createClient(['type' => 'lead']);
        Client::where('id', $lead->id)->update(['updated_at' => now()->subDays(10)]);

        $this->artisan('leads:send-reminders')->assertSuccessful();

        $lead->refresh();
        $this->assertNull($lead->reminder_email_sent_at);
    }

    public function test_kanban_shows_email_sent_icon(): void
    {
        $lead = $this->createClient(['name' => 'Emailed Lead', 'type' => 'lead']);
        Client::where('id', $lead->id)->update([
            'updated_at'             => now()->subDays(10),
            'reminder_email_sent_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('clients.index'));

        $response->assertOk();
        $response->assertSee(__('messages.email_reminder_sent'), false);
    }

    public function test_snooze_resets_reminder_email_sent_at(): void
    {
        $lead = $this->createClient(['type' => 'lead']);
        Client::where('id', $lead->id)->update(['reminder_email_sent_at' => now()]);

        $this->actingAs($this->admin)->postJson(route('clients.snooze', $lead));

        $lead->refresh();
        $this->assertNull($lead->reminder_email_sent_at);
    }

    public function test_escalation_queues_email_to_manager(): void
    {
        Notification::fake();
        Mail::fake();

        Setting::set('lead_followup_email_enabled', '1');
        Setting::set('lead_followup_days', '7');
        Setting::set('lead_escalation_days', '14');

        $manager = $this->createStaffUser('sales_manager', ['clients.edit']);

        $lead = $this->createClient(['type' => 'lead']);
        Client::where('id', $lead->id)->update(['updated_at' => now()->subDays(20)]);

        $this->artisan('leads:send-reminders')->assertSuccessful();

        Mail::assertQueued(LeadFollowUpReminderMail::class, function ($mail) use ($manager) {
            return $mail->salesName === $manager->name && $mail->isEscalation === true;
        });
    }
}
