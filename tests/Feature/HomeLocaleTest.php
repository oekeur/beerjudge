<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Member;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class HomeLocaleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dutch_member_sees_dutch_home_page(): void
    {
        $member = Member::factory()->create(['locale' => 'nl']);
        Competition::forceCreate(['name' => 'Voorjaar', 'year' => 2025, 'status' => 'open', 'judging_date' => '2025-03-15']);
        Competition::forceCreate(['name' => 'Najaar', 'year' => 2025, 'status' => 'judging']);
        Competition::forceCreate(['name' => 'Winter', 'year' => 2024, 'status' => 'closed']);

        $response = $this->actingAs($member)->get('/');

        $response->assertOk()
            ->assertSeeText('Competities')
            ->assertSeeText('Keuren')
            ->assertSeeText('Afgelopen')
            ->assertSeeText('Inzendingen keuren')
            ->assertSeeText('Inzending registreren')
            ->assertSeeText('Bekijk resultaten')
            ->assertSeeText('15 mrt. 2025')
            ->assertDontSeeText('Judge entries')
            ->assertDontSeeText('Register entry')
            ->assertDontSeeText('View results')
            ->assertDontSeeText('Past')
            ->assertSee('title="Lichte modus"', false)
            ->assertSee('title="Systeemvoorkeur"', false)
            ->assertSee('title="Donkere modus"', false)
            ->assertSeeText('Lichte modus')
            ->assertSeeText('Systeemvoorkeur')
            ->assertSeeText('Donkere modus')
            ->assertDontSeeText('Light mode')
            ->assertDontSeeText('System preference')
            ->assertDontSeeText('Dark mode')
            ->assertDontSee('title="Light mode"', false)
            ->assertDontSeeText('15 Mar 2025');
    }

    public function test_dutch_member_sees_dutch_empty_state(): void
    {
        $member = Member::factory()->create(['locale' => 'nl']);

        $this->actingAs($member)->get('/')
            ->assertOk()
            ->assertSeeText('Nog geen competities.')
            ->assertDontSeeText('No competitions yet.');
    }
}
