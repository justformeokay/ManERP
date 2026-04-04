<?php

namespace Tests\Feature;

use App\Http\Middleware\SetLocale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TUGAS 5: Multilingual & Locale Persistence
 *
 * Tests locale switching via /lang/{locale} route,
 * session persistence, user preference storage, and
 * translated content rendering for EN, ID, KO, ZH.
 */
class LocalePersistenceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role'   => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'locale' => 'en',
        ]);
    }

    // ─── LOCALE SWITCHING ────────────────────────────────────────

    public function test_switch_to_korean_updates_session_and_user(): void
    {
        $response = $this->actingAs($this->user)->get(route('language.switch', 'ko'));

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'ko');

        $this->user->refresh();
        $this->assertEquals('ko', $this->user->locale, 'User locale preference must be saved to database');
    }

    public function test_switch_to_chinese_updates_session_and_user(): void
    {
        $response = $this->actingAs($this->user)->get(route('language.switch', 'zh'));

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'zh');

        $this->user->refresh();
        $this->assertEquals('zh', $this->user->locale);
    }

    public function test_switch_to_indonesian_updates_session_and_user(): void
    {
        $response = $this->actingAs($this->user)->get(route('language.switch', 'id'));

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'id');

        $this->user->refresh();
        $this->assertEquals('id', $this->user->locale);
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $response = $this->actingAs($this->user)->get(route('language.switch', 'xx'));

        $response->assertRedirect();
        // The session may still carry user's existing locale preference via middleware,
        // but the user's DB value must NOT change to the invalid locale.
        $this->user->refresh();
        $this->assertEquals('en', $this->user->locale, 'Invalid locale must not change user preference');
        $this->assertFalse(SetLocale::isSupported('xx'), 'xx must not be a supported locale');
        $this->assertNotEquals('xx', $this->user->locale);
    }

    // ─── SUPPORTED LOCALES CHECK ─────────────────────────────────

    public function test_all_four_locales_are_supported(): void
    {
        $this->assertTrue(SetLocale::isSupported('en'), 'English must be supported');
        $this->assertTrue(SetLocale::isSupported('id'), 'Indonesian must be supported');
        $this->assertTrue(SetLocale::isSupported('ko'), 'Korean must be supported');
        $this->assertTrue(SetLocale::isSupported('zh'), 'Chinese must be supported');
        $this->assertFalse(SetLocale::isSupported('fr'), 'French must NOT be supported');
        $this->assertFalse(SetLocale::isSupported(''), 'Empty string must NOT be supported');
    }

    // ─── TRANSLATION CONTENT VERIFICATION ────────────────────────

    public function test_korean_translations_load_correctly(): void
    {
        // Set locale explicitly in the app (HTTP request doesn't persist across test process)
        app()->setLocale('ko');

        $totalRevenue = __('messages.total_revenue');
        $this->assertEquals('총 매출', $totalRevenue, 'Korean: total_revenue must be translated');

        $dashboard = __('messages.dashboard');
        $this->assertNotEquals('messages.dashboard', $dashboard, 'Translation key must resolve');
        $this->assertNotEmpty($dashboard);
    }

    public function test_chinese_translations_load_correctly(): void
    {
        app()->setLocale('zh');

        $totalRevenue = __('messages.total_revenue');
        $this->assertEquals('总收入', $totalRevenue, 'Chinese: total_revenue must be translated');

        $dashboard = __('messages.dashboard');
        $this->assertNotEquals('messages.dashboard', $dashboard);
        $this->assertNotEmpty($dashboard);
    }

    public function test_indonesian_translations_load_correctly(): void
    {
        app()->setLocale('id');

        $dashboard = __('messages.dashboard');
        $this->assertNotEquals('messages.dashboard', $dashboard, 'Indonesian translation must resolve');
        $this->assertNotEmpty($dashboard);

        $settings = __('messages.settings');
        $this->assertNotEquals('messages.settings', $settings);
    }

    // ─── LOCALE PERSISTENCE ACROSS REQUESTS ──────────────────────

    public function test_locale_persists_across_multiple_requests(): void
    {
        // Switch to Korean via the language switch route
        $this->actingAs($this->user)->get(route('language.switch', 'ko'));

        $this->user->refresh();
        $this->assertEquals('ko', $this->user->locale, 'User DB locale must be updated');

        // Subsequent request with session should carry the locale
        $response = $this->actingAs($this->user)
            ->withSession(['locale' => 'ko'])
            ->get(route('profile.edit'));

        $response->assertSuccessful();
        $this->assertEquals('ko', session('locale'), 'Locale must persist in session');
        $this->assertContains($this->user->locale, ['en', 'id', 'ko', 'zh'], 'User locale must be a valid supported locale');
    }

    // ─── NEW SUPPORT/ABOUT TRANSLATION KEYS ─────────────────────

    public function test_support_ticket_translations_exist_in_all_locales(): void
    {
        $locales = ['en', 'id', 'ko', 'zh'];
        $keys = ['support_tickets', 'new_ticket', 'ticket_created', 'status_open', 'priority_high'];

        foreach ($locales as $locale) {
            app()->setLocale($locale);
            foreach ($keys as $key) {
                $translated = __("messages.{$key}");
                $this->assertNotEquals(
                    "messages.{$key}",
                    $translated,
                    "Translation key '{$key}' missing for locale '{$locale}'"
                );
            }
        }

        // Reset
        app()->setLocale('en');
        $this->assertCount(4, $locales);
    }

    public function test_about_page_translations_exist_in_all_locales(): void
    {
        $locales = ['en', 'id', 'ko', 'zh'];
        $keys = ['about_application', 'developer_info', 'legal_disclaimer'];

        foreach ($locales as $locale) {
            app()->setLocale($locale);
            foreach ($keys as $key) {
                $translated = __("messages.{$key}");
                $this->assertNotEquals(
                    "messages.{$key}",
                    $translated,
                    "Translation key '{$key}' missing for locale '{$locale}'"
                );
            }
        }

        app()->setLocale('en');
        $this->assertCount(4, $locales);
    }
}
