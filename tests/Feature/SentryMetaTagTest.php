<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Member;
use App\Models\Style;
use App\Support\SentryFeedbackLabels;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SentryMetaTagTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function makeJudgingFixture(): array
    {
        $comp = Competition::forceCreate(['name' => 'Sentry Cup', 'year' => 2025, 'status' => 'judging']);
        $style = Style::create(['code' => '1A', 'category_name' => 'Standard American', 'name' => 'Light Lager']);
        $comp->styles()->attach($style->id);
        $judge = Member::factory()->create(['name' => 'Judge Joe', 'email' => 'joe@example.com']);

        return [$comp, $judge];
    }

    public function test_sentry_dsn_meta_tag_present_when_dsn_configured(): void
    {
        config(['sentry.dsn' => 'https://abc123@sentry.io/456']);

        [$comp, $judge] = $this->makeJudgingFixture();

        $response = $this->actingAs($judge)->get("/judging/{$comp->id}");

        $response->assertOk();
        $response->assertSee('name="sentry-dsn"', false);
        $response->assertSee('content="https://abc123@sentry.io/456"', false);
    }

    public function test_sentry_dsn_meta_tag_has_empty_content_when_dsn_not_set(): void
    {
        config(['sentry.dsn' => null]);

        [$comp, $judge] = $this->makeJudgingFixture();

        $response = $this->actingAs($judge)->get("/judging/{$comp->id}");

        $response->assertOk();
        $response->assertSee('name="sentry-dsn"', false);
        $response->assertSee('content=""', false);
    }

    public function test_sentry_dsn_meta_tag_has_empty_content_when_dsn_is_empty_string(): void
    {
        config(['sentry.dsn' => '']);

        [$comp, $judge] = $this->makeJudgingFixture();

        $response = $this->actingAs($judge)->get("/judging/{$comp->id}");

        $response->assertOk();
        $response->assertSee('name="sentry-dsn"', false);
        $response->assertSee('content=""', false);
    }

    public function test_sentry_user_meta_tag_contains_authenticated_user_name_and_email(): void
    {
        [$comp, $judge] = $this->makeJudgingFixture();

        $response = $this->actingAs($judge)->get("/judging/{$comp->id}");

        $response->assertOk();
        $response->assertSee('name="sentry-user"', false);
        $response->assertSee('Judge Joe', false);
        $response->assertSee('joe@example.com', false);
    }

    public function test_filament_admin_panel_renders_sentry_meta_tags(): void
    {
        config(['sentry.dsn' => 'https://abc123@sentry.io/456']);

        $admin = Member::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get('/admin/competitions');

        $response->assertOk();
        $response->assertSee('name="sentry-dsn"', false);
        $response->assertSee('content="https://abc123@sentry.io/456"', false);
        $response->assertSee('name="sentry-user"', false);
        $response->assertSee($admin->name, false);
        $response->assertSee($admin->email, false);
    }

    public function test_filament_admin_panel_loads_self_hosted_sentry_bundle(): void
    {
        $admin = Member::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get('/admin/competitions');

        $response->assertOk();
        $response->assertSee('/vendor/error-report/bundle.js', false);
        $response->assertDontSee('browser.sentry-cdn.com', false);
    }

    public function test_filament_admin_panel_renders_feedback_labels_in_member_locale(): void
    {
        $admin = Member::factory()->create(['is_admin' => true, 'locale' => 'nl']);

        $response = $this->actingAs($admin)->get('/admin/competitions');

        $response->assertOk();
        $response->assertSee('name="sentry-feedback-labels"', false);
        $response->assertSee('"triggerLabel":"Meld een bug"', false);
    }

    public function test_member_site_renders_dutch_feedback_labels_for_nl_member(): void
    {
        [$comp, $judge] = $this->makeJudgingFixture();
        $judge->update(['locale' => 'nl']);

        $response = $this->actingAs($judge)->get("/judging/{$comp->id}");

        $response->assertOk();
        $response->assertSee('name="sentry-feedback-labels"', false);
        $response->assertSee('"triggerLabel":"Meld een bug"', false);
        $response->assertSee('"addScreenshotButtonLabel":"Screenshot toevoegen"', false);
    }

    public function test_member_site_renders_english_feedback_labels_for_en_member(): void
    {
        [$comp, $judge] = $this->makeJudgingFixture();
        $judge->update(['locale' => 'en']);

        $response = $this->actingAs($judge)->get("/judging/{$comp->id}");

        $response->assertSee('"triggerLabel":"Report a Bug"', false);

        $this->assertMatchesRegularExpression('/name="sentry-feedback-labels" content=\'([^\']*)\'/', $response->getContent());
        preg_match('/name="sentry-feedback-labels" content=\'([^\']*)\'/', $response->getContent(), $matches);
        $labels = json_decode(html_entity_decode($matches[1]), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame("What's the bug? What did you expect?", $labels['messagePlaceholder']);
    }

    public function test_every_feedback_option_has_an_english_and_dutch_translation(): void
    {
        foreach (['en', 'nl'] as $locale) {
            app()->setLocale($locale);
            foreach (SentryFeedbackLabels::all() as $option => $text) {
                $this->assertNotSame('feedback.'.$option, $text, "Missing {$locale} translation for {$option}");
            }
        }
    }

    public function test_sentry_user_meta_survives_apostrophes_and_markup_in_the_name(): void
    {
        [$comp, $judge] = $this->makeJudgingFixture();
        $judge->update(['name' => "D'Hondt <b>&", 'is_admin' => true]);

        foreach (["/judging/{$comp->id}", '/admin/competitions'] as $url) {
            $content = $this->actingAs($judge)->get($url)->getContent();

            $this->assertSame(1, preg_match('/name="sentry-user" content=\'([^\']*)\'/', $content, $matches), "Attribute truncated on {$url}");
            $user = json_decode(html_entity_decode($matches[1]), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame("D'Hondt <b>&", $user['name']);
            $this->assertStringNotContainsString('<b>', $matches[1]);
        }
    }
}
