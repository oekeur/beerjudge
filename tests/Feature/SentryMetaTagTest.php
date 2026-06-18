<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Member;
use App\Models\Style;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SentryMetaTagTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function makeJudgingFixture(): array
    {
        $comp = Competition::create(['name' => 'Sentry Cup', 'year' => 2025, 'status' => 'judging']);
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

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('name="sentry-dsn"', false);
        $response->assertSee('content="https://abc123@sentry.io/456"', false);
        $response->assertSee('name="sentry-user"', false);
        $response->assertSee($admin->name, false);
        $response->assertSee($admin->email, false);
    }
}
