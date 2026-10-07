<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Style;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_inactive_admin_is_redirected_to_login_and_logged_out(): void
    {
        $admin = Member::factory()->admin()->inactive()->create();

        $this->actingAs($admin)->get('/admin')->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_inactive_admin_is_blocked_on_panel_subpages(): void
    {
        $admin = Member::factory()->admin()->inactive()->create();

        $this->actingAs($admin)->get('/admin/competitions')->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_inactive_admin_sees_inactive_account_error_on_login_page(): void
    {
        $admin = Member::factory()->admin()->inactive()->create(['locale' => 'nl']);

        $this->actingAs($admin)->followingRedirects()->get('/admin')
            ->assertSee('Je account is niet actief.');
    }

    public function test_active_admin_reaches_the_panel(): void
    {
        $admin = Member::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')->assertRedirect('/admin/competitions');
        $this->actingAs($admin)->get('/admin/competitions')->assertOk();
    }

    public function test_deactivated_admin_cannot_save_through_livewire_update(): void
    {
        $admin = Member::factory()->admin()->create();
        $style = Style::create([
            'source' => 'custom', 'code' => 'X1', 'category_name' => 'Cat', 'name' => 'Original',
        ]);

        $html = $this->actingAs($admin)->get("/admin/styles/{$style->id}/edit")->assertOk()->getContent();

        preg_match_all('/wire:snapshot="([^"]+)"/', $html, $matches);
        $snapshot = null;
        foreach ($matches[1] as $raw) {
            $decoded = html_entity_decode($raw, ENT_QUOTES);
            if (str_contains(json_decode($decoded, true)['memo']['name'] ?? '', 'edit-style')) {
                $snapshot = $decoded;
            }
        }
        $this->assertNotNull($snapshot, 'EditStyle snapshot not found');

        $admin->update(['is_active' => false]);

        $this->withHeaders(['X-Livewire' => 'true'])->postJson('/livewire/update', [
            '_token' => csrf_token(),
            'components' => [[
                'snapshot' => $snapshot,
                'updates' => ['data.name' => 'Hacked by inactive admin'],
                'calls' => [['path' => '', 'method' => 'save', 'params' => []]],
            ]],
        ]);

        $this->assertSame('Original', $style->fresh()->name);
    }
}
