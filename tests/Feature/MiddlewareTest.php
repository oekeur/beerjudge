<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Member;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    use LazilyRefreshDatabase;

    // RequireActiveMember

    public function test_inactive_member_is_redirected_to_login(): void
    {
        $member = Member::factory()->create(['is_active' => false]);
        $comp = Competition::create(['name' => 'Cup', 'year' => 2025, 'status' => 'judging']);

        $this->actingAs($member)->get("/judging/{$comp->id}")
            ->assertRedirect('/login');
    }

    public function test_inactive_member_sees_translated_error_on_login_page(): void
    {
        $member = Member::factory()->create(['is_active' => false, 'locale' => 'nl']);
        $comp = Competition::create(['name' => 'Cup', 'year' => 2025, 'status' => 'judging']);

        $this->actingAs($member)->followingRedirects()->get("/judging/{$comp->id}")
            ->assertSee('Je account is niet actief.');
    }

    public function test_active_member_is_not_redirected_to_login(): void
    {
        $member = Member::factory()->create(['is_active' => true]);
        $comp = Competition::create(['name' => 'Cup', 'year' => 2025, 'status' => 'judging']);

        $response = $this->actingAs($member)->get("/judging/{$comp->id}");

        $this->assertNotEquals(route('login'), $response->headers->get('Location'));
    }

    // RequireAdmin

    public function test_non_admin_member_gets_403_on_results_when_competition_not_closed(): void
    {
        $member = Member::factory()->create(['is_admin' => false]);
        $comp = Competition::create(['name' => 'Cup', 'year' => 2025, 'status' => 'judging']);

        $this->actingAs($member)->get("/competitions/{$comp->id}/results")
            ->assertForbidden();
    }

    public function test_non_admin_member_can_access_results_when_competition_is_closed(): void
    {
        $member = Member::factory()->create(['is_admin' => false]);
        $comp = Competition::create(['name' => 'Cup', 'year' => 2025, 'status' => 'closed']);

        $this->actingAs($member)->get("/competitions/{$comp->id}/results")
            ->assertOk();
    }

    public function test_admin_can_access_results_regardless_of_status(): void
    {
        $admin = Member::factory()->create(['is_admin' => true]);
        $comp = Competition::create(['name' => 'Cup', 'year' => 2025, 'status' => 'judging']);

        $this->actingAs($admin)->get("/competitions/{$comp->id}/results")
            ->assertOk();
    }
}
