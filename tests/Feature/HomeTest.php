<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Member;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_member_sees_non_draft_competitions(): void
    {
        $member = Member::factory()->create();
        Competition::create(['name' => 'Hidden Draft', 'year' => 2025, 'status' => 'draft']);
        Competition::create(['name' => 'Spring Open', 'year' => 2025, 'status' => 'open']);
        Competition::create(['name' => 'Autumn Judging', 'year' => 2025, 'status' => 'judging']);

        $response = $this->actingAs($member)->get('/');

        $response->assertOk();
        $response->assertSee('Spring Open');
        $response->assertSee('Autumn Judging');
        $response->assertDontSee('Hidden Draft');
        $response->assertSee('id="theme-toggle"', false);
        $response->assertSee("localStorage.getItem('theme') || 'system'", false);
    }

    public function test_judging_competition_shows_judge_link(): void
    {
        $member = Member::factory()->create();
        $competition = Competition::create(['name' => 'Current Cup', 'year' => 2025, 'status' => 'judging']);

        $response = $this->actingAs($member)->get('/');

        $response->assertOk();
        $response->assertSee(route('judging.show', $competition));
    }

    public function test_closed_competition_shows_results_link(): void
    {
        $member = Member::factory()->create();
        $competition = Competition::create(['name' => 'Past Cup', 'year' => 2024, 'status' => 'closed']);

        $response = $this->actingAs($member)->get('/');

        $response->assertOk();
        $response->assertSee(route('results.show', [$competition, $member]));
    }

    public function test_admin_sees_admin_panel_link(): void
    {
        $admin = Member::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get('/');

        $response->assertOk();
        $response->assertSee('/admin');
    }

    public function test_regular_member_does_not_see_admin_panel_link(): void
    {
        $member = Member::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($member)->get('/');

        $response->assertOk();
        $response->assertDontSee('Admin panel');
    }
}
