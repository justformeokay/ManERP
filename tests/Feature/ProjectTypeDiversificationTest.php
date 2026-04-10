<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTypeDiversificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'   => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
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

    private function salesProjectData(array $overrides = []): array
    {
        $client = $this->createClient();

        return array_merge([
            'type'             => 'sales',
            'name'             => 'Sales Project Alpha',
            'client_id'        => $client->id,
            'status'           => 'draft',
            'start_date'       => now()->toDateString(),
            'end_date'         => now()->addMonths(3)->toDateString(),
            'budget'           => 50000000,
            'estimated_budget' => 45000000,
            'description'      => 'A client-facing sales project.',
        ], $overrides);
    }

    private function capexProjectData(array $overrides = []): array
    {
        return array_merge([
            'type'             => 'internal_capex',
            'name'             => 'Server Room Upgrade',
            'client_id'        => null,
            'status'           => 'draft',
            'start_date'       => now()->toDateString(),
            'end_date'         => now()->addMonths(6)->toDateString(),
            'budget'           => 200000000,
            'estimated_budget' => 180000000,
            'description'      => 'Internal capital expenditure project.',
        ], $overrides);
    }

    // ═══════════════════════════════════════════════════════════════
    // 1. MIGRATION & SCHEMA
    // ═══════════════════════════════════════════════════════════════

    public function test_projects_table_has_type_column(): void
    {
        $this->assertTrue(
            \Schema::hasColumn('projects', 'type'),
            'projects table should have a type column'
        );
    }

    public function test_projects_table_has_estimated_budget_column(): void
    {
        $this->assertTrue(
            \Schema::hasColumn('projects', 'estimated_budget'),
            'projects table should have an estimated_budget column'
        );
    }

    public function test_type_column_defaults_to_sales(): void
    {
        $project = Project::create([
            'name'       => 'Default Type',
            'client_id'  => $this->createClient()->id,
            'status'     => 'draft',
            'start_date' => now()->toDateString(),
        ]);

        $this->assertEquals('sales', $project->fresh()->type);
    }

    // ═══════════════════════════════════════════════════════════════
    // 2. MODEL HELPERS
    // ═══════════════════════════════════════════════════════════════

    public function test_type_options_returns_expected_values(): void
    {
        $options = Project::typeOptions();
        $this->assertEquals(['sales', 'internal_capex'], $options);
    }

    public function test_type_colors_returns_all_types(): void
    {
        $colors = Project::typeColors();
        $this->assertArrayHasKey('sales', $colors);
        $this->assertArrayHasKey('internal_capex', $colors);
    }

    public function test_is_sales_helper(): void
    {
        $project = new Project(['type' => 'sales']);
        $this->assertTrue($project->isSales());
        $this->assertFalse($project->isCapex());
    }

    public function test_is_capex_helper(): void
    {
        $project = new Project(['type' => 'internal_capex']);
        $this->assertTrue($project->isCapex());
        $this->assertFalse($project->isSales());
    }

    public function test_type_label_returns_translated_string(): void
    {
        $project = new Project(['type' => 'sales']);
        $label = $project->typeLabel();
        $this->assertNotEmpty($label);
        $this->assertStringNotContainsString('messages.', $label);
    }

    // ═══════════════════════════════════════════════════════════════
    // 3. STORE — SALES PROJECT
    // ═══════════════════════════════════════════════════════════════

    public function test_store_sales_project_requires_client(): void
    {
        $this->actingAs($this->admin)
            ->post(route('projects.store'), $this->salesProjectData(['client_id' => null]))
            ->assertSessionHasErrors('client_id');
    }

    public function test_store_sales_project_success(): void
    {
        $data = $this->salesProjectData();

        $response = $this->actingAs($this->admin)
            ->post(route('projects.store'), $data);

        $response->assertRedirect(route('projects.index'));

        $this->assertDatabaseHas('projects', [
            'type'             => 'sales',
            'name'             => 'Sales Project Alpha',
            'client_id'        => $data['client_id'],
            'estimated_budget' => '45000000.00',
        ]);
    }

    public function test_store_sales_project_auto_generates_code(): void
    {
        $this->actingAs($this->admin)
            ->post(route('projects.store'), $this->salesProjectData());

        $project = Project::latest('id')->first();
        $this->assertMatchesRegularExpression('/^PRJ-\d{4}$/', $project->code);
    }

    // ═══════════════════════════════════════════════════════════════
    // 4. STORE — CAPEX PROJECT
    // ═══════════════════════════════════════════════════════════════

    public function test_store_capex_project_without_client_success(): void
    {
        $data = $this->capexProjectData();

        $response = $this->actingAs($this->admin)
            ->post(route('projects.store'), $data);

        $response->assertRedirect(route('projects.index'));

        $this->assertDatabaseHas('projects', [
            'type'      => 'internal_capex',
            'name'      => 'Server Room Upgrade',
            'client_id' => null,
        ]);
    }

    public function test_store_capex_project_strips_client_id(): void
    {
        $client = $this->createClient();
        $data = $this->capexProjectData(['client_id' => $client->id]);

        $this->actingAs($this->admin)
            ->post(route('projects.store'), $data);

        $project = Project::where('name', 'Server Room Upgrade')->first();
        $this->assertNull($project->client_id, 'CAPEX projects should have client_id nullified');
    }

    public function test_store_capex_project_with_estimated_budget(): void
    {
        $data = $this->capexProjectData(['estimated_budget' => 250000000]);

        $this->actingAs($this->admin)
            ->post(route('projects.store'), $data);

        $this->assertDatabaseHas('projects', [
            'type'             => 'internal_capex',
            'estimated_budget' => '250000000.00',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // 5. VALIDATION
    // ═══════════════════════════════════════════════════════════════

    public function test_type_must_be_valid(): void
    {
        $data = $this->salesProjectData(['type' => 'invalid_type']);

        $this->actingAs($this->admin)
            ->post(route('projects.store'), $data)
            ->assertSessionHasErrors('type');
    }

    public function test_type_is_required(): void
    {
        $data = $this->salesProjectData();
        unset($data['type']);

        $this->actingAs($this->admin)
            ->post(route('projects.store'), $data)
            ->assertSessionHasErrors('type');
    }

    public function test_estimated_budget_must_be_numeric(): void
    {
        $data = $this->salesProjectData(['estimated_budget' => 'not-a-number']);

        $this->actingAs($this->admin)
            ->post(route('projects.store'), $data)
            ->assertSessionHasErrors('estimated_budget');
    }

    public function test_estimated_budget_must_be_non_negative(): void
    {
        $data = $this->salesProjectData(['estimated_budget' => -1000]);

        $this->actingAs($this->admin)
            ->post(route('projects.store'), $data)
            ->assertSessionHasErrors('estimated_budget');
    }

    public function test_estimated_budget_is_nullable(): void
    {
        $data = $this->salesProjectData(['estimated_budget' => null]);

        $this->actingAs($this->admin)
            ->post(route('projects.store'), $data)
            ->assertRedirect(route('projects.index'));
    }

    public function test_manager_id_is_validated(): void
    {
        $data = $this->salesProjectData(['manager_id' => 99999]);

        $this->actingAs($this->admin)
            ->post(route('projects.store'), $data)
            ->assertSessionHasErrors('manager_id');
    }

    // ═══════════════════════════════════════════════════════════════
    // 6. UPDATE
    // ═══════════════════════════════════════════════════════════════

    public function test_update_project_type_from_sales_to_capex(): void
    {
        $client = $this->createClient();
        $project = Project::create([
            'type'       => 'sales',
            'name'       => 'Convert Me',
            'client_id'  => $client->id,
            'status'     => 'draft',
            'start_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->admin)
            ->put(route('projects.update', $project), [
                'type'       => 'internal_capex',
                'name'       => 'Convert Me',
                'status'     => 'draft',
                'start_date' => now()->toDateString(),
            ]);

        $project->refresh();
        $this->assertEquals('internal_capex', $project->type);
        $this->assertNull($project->client_id);
    }

    public function test_update_capex_to_sales_requires_client(): void
    {
        $project = Project::create([
            'type'       => 'internal_capex',
            'name'       => 'Was CAPEX',
            'status'     => 'draft',
            'start_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->admin)
            ->put(route('projects.update', $project), [
                'type'       => 'sales',
                'name'       => 'Was CAPEX',
                'status'     => 'draft',
                'start_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('client_id');
    }

    // ═══════════════════════════════════════════════════════════════
    // 7. INDEX — FILTERING
    // ═══════════════════════════════════════════════════════════════

    public function test_index_shows_both_project_types(): void
    {
        $client = $this->createClient();
        Project::create(['type' => 'sales', 'name' => 'Sales One', 'client_id' => $client->id, 'status' => 'active', 'start_date' => now()]);
        Project::create(['type' => 'internal_capex', 'name' => 'CAPEX One', 'status' => 'active', 'start_date' => now()]);

        $response = $this->actingAs($this->admin)->get(route('projects.index'));
        $response->assertSee('Sales One');
        $response->assertSee('CAPEX One');
    }

    public function test_index_filter_by_sales_type(): void
    {
        $client = $this->createClient();
        Project::create(['type' => 'sales', 'name' => 'Sales Visible', 'client_id' => $client->id, 'status' => 'active', 'start_date' => now()]);
        Project::create(['type' => 'internal_capex', 'name' => 'CAPEX Hidden', 'status' => 'active', 'start_date' => now()]);

        $response = $this->actingAs($this->admin)->get(route('projects.index', ['type' => 'sales']));
        $response->assertSee('Sales Visible');
        $response->assertDontSee('CAPEX Hidden');
    }

    public function test_index_filter_by_capex_type(): void
    {
        $client = $this->createClient();
        Project::create(['type' => 'sales', 'name' => 'Sales Hidden', 'client_id' => $client->id, 'status' => 'active', 'start_date' => now()]);
        Project::create(['type' => 'internal_capex', 'name' => 'CAPEX Visible', 'status' => 'active', 'start_date' => now()]);

        $response = $this->actingAs($this->admin)->get(route('projects.index', ['type' => 'internal_capex']));
        $response->assertDontSee('Sales Hidden');
        $response->assertSee('CAPEX Visible');
    }

    // ═══════════════════════════════════════════════════════════════
    // 8. INDEX — TYPE BADGE RENDERING
    // ═══════════════════════════════════════════════════════════════

    public function test_index_shows_type_badge_for_sales(): void
    {
        $client = $this->createClient();
        Project::create(['type' => 'sales', 'name' => 'Badge Sales', 'client_id' => $client->id, 'status' => 'draft', 'start_date' => now()]);

        $response = $this->actingAs($this->admin)->get(route('projects.index'));
        $response->assertSee(__('messages.project_type_sales'));
    }

    public function test_index_shows_type_badge_for_capex(): void
    {
        Project::create(['type' => 'internal_capex', 'name' => 'Badge CAPEX', 'status' => 'draft', 'start_date' => now()]);

        $response = $this->actingAs($this->admin)->get(route('projects.index'));
        $response->assertSee(__('messages.project_type_internal_capex'));
    }

    public function test_index_capex_shows_internal_project_label(): void
    {
        Project::create(['type' => 'internal_capex', 'name' => 'No Client', 'status' => 'draft', 'start_date' => now()]);

        $response = $this->actingAs($this->admin)->get(route('projects.index'));
        $response->assertSee(__('messages.internal_project'));
    }

    // ═══════════════════════════════════════════════════════════════
    // 9. SHOW VIEW
    // ═══════════════════════════════════════════════════════════════

    public function test_show_displays_type_badge(): void
    {
        $project = Project::create(['type' => 'internal_capex', 'name' => 'Show CAPEX', 'status' => 'draft', 'start_date' => now()]);

        $response = $this->actingAs($this->admin)->get(route('projects.show', $project));
        $response->assertSee(__('messages.project_type_internal_capex'));
    }

    public function test_show_displays_estimated_budget(): void
    {
        $project = Project::create([
            'type'             => 'sales',
            'name'             => 'Budget Show',
            'client_id'        => $this->createClient()->id,
            'status'           => 'draft',
            'start_date'       => now(),
            'estimated_budget' => 75000000,
        ]);

        $response = $this->actingAs($this->admin)->get(route('projects.show', $project));
        $response->assertSee(__('messages.estimated_budget'));
    }

    public function test_show_capex_displays_internal_project_message(): void
    {
        $project = Project::create(['type' => 'internal_capex', 'name' => 'CAPEX Show', 'status' => 'draft', 'start_date' => now()]);

        $response = $this->actingAs($this->admin)->get(route('projects.show', $project));
        $response->assertSee(__('messages.internal_project'));
    }

    // ═══════════════════════════════════════════════════════════════
    // 10. FORM VIEW
    // ═══════════════════════════════════════════════════════════════

    public function test_create_form_shows_type_selector(): void
    {
        $response = $this->actingAs($this->admin)->get(route('projects.create'));
        $response->assertSee(__('messages.project_type_label'));
        $response->assertSee(__('messages.project_type_sales'));
        $response->assertSee(__('messages.project_type_internal_capex'));
    }

    public function test_edit_form_shows_type_selector(): void
    {
        $project = Project::create(['type' => 'internal_capex', 'name' => 'Edit Form', 'status' => 'draft', 'start_date' => now()]);

        $response = $this->actingAs($this->admin)->get(route('projects.edit', $project));
        $response->assertSee(__('messages.project_type_label'));
        $response->assertSee('internal_capex');
    }

    public function test_form_shows_estimated_budget_field(): void
    {
        $response = $this->actingAs($this->admin)->get(route('projects.create'));
        $response->assertSee(__('messages.estimated_budget'));
        $response->assertSee('estimated_budget');
    }

    // ═══════════════════════════════════════════════════════════════
    // 11. I18N
    // ═══════════════════════════════════════════════════════════════

    public function test_translation_keys_exist_in_en(): void
    {
        $keys = [
            'messages.project_type_sales',
            'messages.project_type_internal_capex',
            'messages.project_type_label',
            'messages.project_type_hint',
            'messages.project_type_sales_desc',
            'messages.project_type_capex_desc',
            'messages.all_types',
            'messages.type_column',
            'messages.internal_project',
            'messages.estimated_budget',
            'messages.project_details',
            'messages.project_name',
            'messages.project_manager',
            'messages.timeline_and_budget',
            'messages.start_date',
            'messages.end_date',
            'messages.edit_project',
            'messages.create_project',
            'messages.update_project',
            'messages.danger_zone',
            'messages.delete_project',
        ];

        foreach ($keys as $key) {
            $translated = __($key, [], 'en');
            $this->assertNotEquals($key, $translated, "Missing EN translation for: {$key}");
        }
    }

    public function test_translation_keys_exist_in_id(): void
    {
        $keys = [
            'messages.project_type_sales',
            'messages.project_type_internal_capex',
            'messages.project_type_label',
            'messages.all_types',
            'messages.type_column',
            'messages.internal_project',
            'messages.estimated_budget',
            'messages.project_details',
            'messages.project_name',
            'messages.project_manager',
            'messages.timeline_and_budget',
            'messages.start_date',
            'messages.end_date',
        ];

        foreach ($keys as $key) {
            $translated = __($key, [], 'id');
            $this->assertNotEquals($key, $translated, "Missing ID translation for: {$key}");
        }
    }
}
