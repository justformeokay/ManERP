<?php

namespace Tests\Feature;

use App\Models\EducationArticle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademyEducationTest extends TestCase
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

    private function createArticle(array $overrides = []): EducationArticle
    {
        return EducationArticle::create(array_merge([
            'title'      => 'Test Article',
            'slug'       => 'test-article-' . mt_rand(1000, 9999),
            'category'   => 'glossary',
            'content'    => '## Test Content\n\nThis is a test article with **markdown** content.',
            'icon'       => '📚',
            'sort_order' => 0,
            'is_active'  => true,
        ], $overrides));
    }

    // ═══════════════════════════════════════════════════════════════
    // 1. MIGRATION & SCHEMA
    // ═══════════════════════════════════════════════════════════════

    public function test_education_articles_table_exists(): void
    {
        $this->assertTrue(
            \Schema::hasTable('education_articles'),
            'education_articles table should exist'
        );
    }

    public function test_education_articles_has_required_columns(): void
    {
        $columns = ['id', 'title', 'slug', 'category', 'content', 'icon', 'sort_order', 'is_active', 'created_at', 'updated_at'];

        foreach ($columns as $column) {
            $this->assertTrue(
                \Schema::hasColumn('education_articles', $column),
                "education_articles should have '{$column}' column"
            );
        }
    }

    public function test_slug_is_unique(): void
    {
        $this->createArticle(['slug' => 'unique-slug']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->createArticle(['slug' => 'unique-slug']);
    }

    public function test_category_enum_values(): void
    {
        foreach (['glossary', 'workflow', 'tutorial'] as $cat) {
            $article = $this->createArticle([
                'slug'     => "cat-{$cat}",
                'category' => $cat,
            ]);
            $this->assertEquals($cat, $article->category);
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // 2. MODEL
    // ═══════════════════════════════════════════════════════════════

    public function test_model_casts_is_active_to_boolean(): void
    {
        $article = $this->createArticle(['is_active' => 1]);
        $this->assertIsBool($article->is_active);
        $this->assertTrue($article->is_active);
    }

    public function test_model_casts_sort_order_to_integer(): void
    {
        $article = $this->createArticle(['sort_order' => '5']);
        $this->assertIsInt($article->sort_order);
        $this->assertEquals(5, $article->sort_order);
    }

    public function test_scope_active_filters_inactive(): void
    {
        $this->createArticle(['slug' => 'active-one', 'is_active' => true]);
        $this->createArticle(['slug' => 'inactive-one', 'is_active' => false]);

        $active = EducationArticle::active()->get();
        $this->assertCount(1, $active);
        $this->assertEquals('active-one', $active->first()->slug);
    }

    public function test_scope_category_filters_by_category(): void
    {
        $this->createArticle(['slug' => 'g1', 'category' => 'glossary']);
        $this->createArticle(['slug' => 'w1', 'category' => 'workflow']);
        $this->createArticle(['slug' => 't1', 'category' => 'tutorial']);

        $glossary = EducationArticle::category('glossary')->get();
        $this->assertCount(1, $glossary);
        $this->assertEquals('g1', $glossary->first()->slug);
    }

    public function test_scope_search_matches_title(): void
    {
        $this->createArticle(['slug' => 's1', 'title' => 'Understanding CAPEX']);
        $this->createArticle(['slug' => 's2', 'title' => 'About OPEX']);

        $results = EducationArticle::search('CAPEX')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('s1', $results->first()->slug);
    }

    public function test_scope_search_matches_content(): void
    {
        $this->createArticle(['slug' => 'c1', 'title' => 'Article A', 'content' => 'Contains inventory data']);
        $this->createArticle(['slug' => 'c2', 'title' => 'Article B', 'content' => 'About something else']);

        $results = EducationArticle::search('inventory')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('c1', $results->first()->slug);
    }

    public function test_scope_search_returns_all_when_null(): void
    {
        $this->createArticle(['slug' => 'n1']);
        $this->createArticle(['slug' => 'n2']);

        $results = EducationArticle::search(null)->get();
        $this->assertCount(2, $results);
    }

    public function test_rendered_content_returns_html(): void
    {
        $article = $this->createArticle([
            'slug'    => 'render-test',
            'content' => "## Hello World\n\nThis is **bold** text.",
        ]);

        $html = $article->rendered_content;
        $this->assertStringContainsString('<h2>', $html);
        $this->assertStringContainsString('<strong>bold</strong>', $html);
    }

    public function test_category_options(): void
    {
        $options = EducationArticle::categoryOptions();
        $this->assertEquals(['glossary', 'workflow', 'tutorial'], $options);
    }

    // ═══════════════════════════════════════════════════════════════
    // 3. CONTROLLER — INDEX
    // ═══════════════════════════════════════════════════════════════

    public function test_index_page_loads_for_authenticated_user(): void
    {
        $response = $this->actingAs($this->admin)->get(route('academy.index'));
        $response->assertStatus(200);
        $response->assertViewIs('academy.index');
    }

    public function test_index_redirects_unauthenticated_user(): void
    {
        $response = $this->get(route('academy.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_index_displays_articles(): void
    {
        $article = $this->createArticle(['slug' => 'idx-1', 'title' => 'ManERP Academy Test']);

        $response = $this->actingAs($this->admin)->get(route('academy.index'));
        $response->assertStatus(200);
        $response->assertSee('ManERP Academy Test');
    }

    public function test_index_search_filters_articles(): void
    {
        $this->createArticle(['slug' => 'match', 'title' => 'Capital Expenditure Guide']);
        $this->createArticle(['slug' => 'nomatch', 'title' => 'Payroll Process']);

        $response = $this->actingAs($this->admin)->get(route('academy.index', ['search' => 'Capital']));
        $response->assertStatus(200);
        $response->assertSee('Capital Expenditure Guide');
        $response->assertDontSee('Payroll Process');
    }

    public function test_index_passes_stats(): void
    {
        $this->createArticle(['slug' => 'gs1', 'category' => 'glossary']);
        $this->createArticle(['slug' => 'ws1', 'category' => 'workflow']);

        $response = $this->actingAs($this->admin)->get(route('academy.index'));
        $response->assertViewHas('stats');
    }

    // ═══════════════════════════════════════════════════════════════
    // 4. CONTROLLER — GLOSSARY
    // ═══════════════════════════════════════════════════════════════

    public function test_glossary_page_loads(): void
    {
        $response = $this->actingAs($this->admin)->get(route('academy.glossary'));
        $response->assertStatus(200);
        $response->assertViewIs('academy.glossary');
    }

    public function test_glossary_groups_by_first_letter(): void
    {
        $this->createArticle(['slug' => 'alpha', 'title' => 'Alpha Term', 'category' => 'glossary']);
        $this->createArticle(['slug' => 'bravo', 'title' => 'Bravo Term', 'category' => 'glossary']);

        $response = $this->actingAs($this->admin)->get(route('academy.glossary'));
        $response->assertStatus(200);
        $response->assertSee('Alpha Term');
        $response->assertSee('Bravo Term');
    }

    public function test_glossary_letter_filter(): void
    {
        $this->createArticle(['slug' => 'a-term', 'title' => 'Asset', 'category' => 'glossary']);
        $this->createArticle(['slug' => 'b-term', 'title' => 'Budget Planning Guide', 'category' => 'glossary']);

        $response = $this->actingAs($this->admin)->get(route('academy.glossary', ['letter' => 'A']));
        $response->assertStatus(200);
        $response->assertSee('Asset');
        $response->assertDontSee('Budget Planning Guide');
    }

    public function test_glossary_search_filter(): void
    {
        $this->createArticle(['slug' => 'cap', 'title' => 'CAPEX Definition', 'category' => 'glossary']);
        $this->createArticle(['slug' => 'op', 'title' => 'OPEX Definition', 'category' => 'glossary']);

        $response = $this->actingAs($this->admin)->get(route('academy.glossary', ['search' => 'CAPEX']));
        $response->assertStatus(200);
        $response->assertSee('CAPEX Definition');
        $response->assertDontSee('OPEX Definition');
    }

    // ═══════════════════════════════════════════════════════════════
    // 5. CONTROLLER — WORKFLOWS
    // ═══════════════════════════════════════════════════════════════

    public function test_workflows_page_loads(): void
    {
        $response = $this->actingAs($this->admin)->get(route('academy.workflows'));
        $response->assertStatus(200);
        $response->assertViewIs('academy.workflows');
    }

    public function test_workflows_shows_only_workflow_articles(): void
    {
        $this->createArticle(['slug' => 'wf1', 'title' => 'Sales Workflow', 'category' => 'workflow']);
        $this->createArticle(['slug' => 'gl1', 'title' => 'Glossary Term', 'category' => 'glossary']);

        $response = $this->actingAs($this->admin)->get(route('academy.workflows'));
        $response->assertStatus(200);
        $response->assertSee('Sales Workflow');
        $response->assertDontSee('Glossary Term');
    }

    // ═══════════════════════════════════════════════════════════════
    // 6. CONTROLLER — SHOW
    // ═══════════════════════════════════════════════════════════════

    public function test_show_renders_active_article(): void
    {
        $article = $this->createArticle([
            'slug'    => 'show-test',
            'title'   => 'Detailed Article',
            'content' => '## Detail Content',
        ]);

        $response = $this->actingAs($this->admin)->get(route('academy.show', $article));
        $response->assertStatus(200);
        $response->assertViewIs('academy.show');
        $response->assertSee('Detailed Article');
    }

    public function test_show_returns_404_for_inactive_article(): void
    {
        $article = $this->createArticle([
            'slug'      => 'inactive-test',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('academy.show', $article));
        $response->assertStatus(404);
    }

    public function test_show_loads_related_articles(): void
    {
        $article = $this->createArticle(['slug' => 'main-art', 'category' => 'glossary']);
        $this->createArticle(['slug' => 'related-1', 'category' => 'glossary', 'title' => 'Related One']);
        $this->createArticle(['slug' => 'unrelated', 'category' => 'workflow', 'title' => 'Unrelated']);

        $response = $this->actingAs($this->admin)->get(route('academy.show', $article));
        $response->assertStatus(200);
        $response->assertViewHas('related');
    }

    // ═══════════════════════════════════════════════════════════════
    // 7. CONTROLLER — TOOLTIP API
    // ═══════════════════════════════════════════════════════════════

    public function test_tooltip_returns_json_for_existing_article(): void
    {
        $this->createArticle([
            'slug'    => 'tooltip-test',
            'title'   => 'Tooltip Title',
            'content' => "First paragraph text.\n\nSecond paragraph.",
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('academy.tooltip', ['slug' => 'tooltip-test']));
        $response->assertStatus(200);
        $response->assertJsonStructure(['title', 'content']);
        $response->assertJsonFragment(['title' => 'Tooltip Title']);
    }

    public function test_tooltip_returns_404_for_missing_slug(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('academy.tooltip', ['slug' => 'nonexistent']));
        $response->assertStatus(404);
    }

    public function test_tooltip_returns_404_for_inactive_article(): void
    {
        $this->createArticle([
            'slug'      => 'inactive-tooltip',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('academy.tooltip', ['slug' => 'inactive-tooltip']));
        $response->assertStatus(404);
    }

    public function test_tooltip_strips_markdown_from_content(): void
    {
        $this->createArticle([
            'slug'    => 'strip-md',
            'title'   => 'Markdown Strip',
            'content' => "## Heading\n\nPlain text line.\n\nAnother paragraph.",
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('academy.tooltip', ['slug' => 'strip-md']));
        $response->assertStatus(200);
        $content = $response->json('content');
        $this->assertStringNotContainsString('##', $content);
    }

    // ═══════════════════════════════════════════════════════════════
    // 8. SEEDER
    // ═══════════════════════════════════════════════════════════════

    public function test_seeder_creates_articles(): void
    {
        $this->seed(\Database\Seeders\EducationArticleSeeder::class);

        $this->assertGreaterThanOrEqual(10, EducationArticle::count());
        $this->assertDatabaseHas('education_articles', ['slug' => 'capex']);
        $this->assertDatabaseHas('education_articles', ['slug' => 'debit-note']);
        $this->assertDatabaseHas('education_articles', ['slug' => 'credit-note']);
        $this->assertDatabaseHas('education_articles', ['slug' => 'anti-overclaim']);
        $this->assertDatabaseHas('education_articles', ['slug' => 'pr-vs-po']);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(\Database\Seeders\EducationArticleSeeder::class);
        $countFirst = EducationArticle::count();

        $this->seed(\Database\Seeders\EducationArticleSeeder::class);
        $countSecond = EducationArticle::count();

        $this->assertEquals($countFirst, $countSecond);
    }

    public function test_seeder_has_all_categories(): void
    {
        $this->seed(\Database\Seeders\EducationArticleSeeder::class);

        foreach (['glossary', 'workflow', 'tutorial'] as $cat) {
            $this->assertGreaterThan(
                0,
                EducationArticle::where('category', $cat)->count(),
                "Seeder should include at least one {$cat} article"
            );
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // 9. ROUTES
    // ═══════════════════════════════════════════════════════════════

    public function test_all_academy_routes_exist(): void
    {
        $routes = ['academy.index', 'academy.glossary', 'academy.workflows', 'academy.tooltip', 'academy.show'];

        foreach ($routes as $routeName) {
            $this->assertTrue(
                \Route::has($routeName),
                "Route '{$routeName}' should be registered"
            );
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // 10. I18N
    // ═══════════════════════════════════════════════════════════════

    public function test_i18n_keys_exist_in_all_languages(): void
    {
        $keys = [
            'academy_title', 'academy_subtitle', 'academy_glossary',
            'academy_workflows', 'academy_tutorials', 'academy_search_placeholder',
            'academy_read_more', 'academy_related', 'academy_learn_more',
        ];

        foreach (['en', 'id', 'zh', 'ko'] as $locale) {
            foreach ($keys as $key) {
                $translated = __("messages.{$key}", [], $locale);
                $this->assertNotEquals(
                    "messages.{$key}",
                    $translated,
                    "Key 'messages.{$key}' should be translated in '{$locale}'"
                );
            }
        }
    }
}
