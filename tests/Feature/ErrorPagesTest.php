<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Member;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_judging_a_closed_competition_shows_the_translated_403_page(): void
    {
        $competition = Competition::forceCreate(['name' => 'Closed Cup', 'year' => 2025, 'status' => 'closed']);
        $judge = Member::factory()->create(['locale' => 'nl']);

        $this->actingAs($judge)->get("/judging/{$competition->id}")
            ->assertForbidden()
            ->assertSee('Geen toegang tot deze pagina')
            ->assertSee('het keuren voor deze competitie waarschijnlijk afgesloten')
            ->assertSee('Terug naar home');
    }

    public function test_403_page_is_english_for_an_english_member(): void
    {
        $competition = Competition::forceCreate(['name' => 'Closed Cup', 'year' => 2025, 'status' => 'closed']);
        $judge = Member::factory()->create(['locale' => 'en']);

        $this->actingAs($judge)->get("/judging/{$competition->id}")
            ->assertForbidden()
            ->assertSee('No access to this page')
            ->assertSee('Back to home');
    }

    public function test_throttled_magic_link_shows_the_translated_429_page(): void
    {
        Cache::flush();
        $headers = ['Accept-Language' => 'nl'];

        for ($i = 0; $i < 5; $i++) {
            $this->get('/magic-link/sametoken', $headers);
        }

        $this->get('/magic-link/sametoken', $headers)
            ->assertStatus(429)
            ->assertSee('Te veel pogingen')
            ->assertSee('Wacht een paar minuten en probeer het opnieuw.');
    }

    public function test_json_requests_still_get_a_json_403(): void
    {
        $competition = Competition::forceCreate(['name' => 'Closed Cup', 'year' => 2025, 'status' => 'closed']);
        $judge = Member::factory()->create();

        $this->actingAs($judge)->getJson("/judging/{$competition->id}")
            ->assertForbidden()
            ->assertJsonStructure(['message']);
    }
}
