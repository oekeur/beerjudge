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

    public function test_active_member_is_not_redirected_to_login(): void
    {
        $member = Member::factory()->create(['is_active' => true]);
        $comp = Competition::create(['name' => 'Cup', 'year' => 2025, 'status' => 'judging']);

        $response = $this->actingAs($member)->get("/judging/{$comp->id}");

        $this->assertNotEquals(route('login'), $response->headers->get('Location'));
    }

    // RequireAdmin

    public function test_non_admin_member_gets_403_on_admin_route(): void
    {
        $member = Member::factory()->create(['is_admin' => false]);
        $comp = Competition::create(['name' => 'Cup', 'year' => 2025, 'status' => 'closed']);

        $this->actingAs($member)->get("/competitions/{$comp->id}/results")
            ->assertForbidden();
    }

    public function test_admin_passes_admin_middleware(): void
    {
        $admin = Member::factory()->create(['is_admin' => true]);
        $comp = Competition::create(['name' => 'Cup', 'year' => 2025, 'status' => 'closed']);

        $this->actingAs($admin)->get("/competitions/{$comp->id}/results")
            ->assertOk();
    }
}
