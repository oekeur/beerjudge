<?php

namespace Tests\Feature;

use App\Models\MagicLink;
use App\Models\Member;
use App\Notifications\MagicLinkNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MagicLinkTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_magic_link_request_form_is_accessible(): void
    {
        $this->get('/magic-link')->assertOk();
    }

    public function test_valid_email_queues_notification(): void
    {
        Notification::fake();

        $member = Member::factory()->create();

        $this->post('/magic-link', ['email' => $member->email])
            ->assertRedirect();

        Notification::assertSentTo($member, MagicLinkNotification::class);
    }

    public function test_unknown_email_returns_success_without_notification(): void
    {
        Notification::fake();

        $this->post('/magic-link', ['email' => 'nobody@example.com'])
            ->assertRedirect();

        Notification::assertNothingSent();
    }

    public function test_get_with_valid_token_shows_login_button_without_consuming(): void
    {
        $member = Member::factory()->create();
        $plain = MagicLink::createForMember($member);

        $this->get('/magic-link/'.$plain)
            ->assertOk()
            ->assertSee('action="'.route('auth.magic-link.consume', ['token' => $plain]).'"', false)
            ->assertSee('data-testid="magic-link-login-button"', false);

        $this->assertGuest();
        $this->assertNull(MagicLink::findByPlaintext($plain)?->used_at);
    }

    public function test_post_with_valid_token_logs_member_in_and_marks_used(): void
    {
        $member = Member::factory()->create();
        $plain = MagicLink::createForMember($member);

        $this->post('/magic-link/'.$plain)
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($member);
        $this->assertNotNull($member->fresh()->remember_token);
        $this->assertNotNull(MagicLink::findByPlaintext($plain)?->used_at);
    }

    public function test_post_redirects_admin_to_admin_panel(): void
    {
        $member = Member::factory()->admin()->create();
        $plain = MagicLink::createForMember($member);

        $this->post('/magic-link/'.$plain)
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($member);
    }

    public function test_expired_magic_link_redirects_with_error_on_get_and_post(): void
    {
        $member = Member::factory()->create();
        $plain = MagicLink::createForMember($member);

        MagicLink::where('member_id', $member->id)->update(['expires_at' => now()->subMinute()]);

        $this->from('/login')->get('/magic-link/'.$plain)
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => __('auth.magic_link_invalid')]);

        $this->from('/login')->post('/magic-link/'.$plain)
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => __('auth.magic_link_invalid')]);

        $this->assertGuest();
    }

    public function test_used_magic_link_is_rejected_on_get_and_post(): void
    {
        $member = Member::factory()->create();
        $plain = MagicLink::createForMember($member);

        $this->post('/magic-link/'.$plain);
        $this->post('/logout');

        $this->get('/magic-link/'.$plain)
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => __('auth.magic_link_invalid')]);

        $this->post('/magic-link/'.$plain)
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => __('auth.magic_link_invalid')]);

        $this->assertGuest();
    }

    public function test_invalid_token_redirects_to_login_on_get_and_post(): void
    {
        $this->get('/magic-link/totallybogustoken')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => __('auth.magic_link_invalid')]);

        $this->post('/magic-link/totallybogustoken')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => __('auth.magic_link_invalid')]);
    }

    public function test_consume_only_succeeds_once_per_link(): void
    {
        $member = Member::factory()->create();
        $link = MagicLink::findByPlaintext(MagicLink::createForMember($member));
        $this->assertNotNull($link);

        $this->assertTrue($link->consume());
        $this->assertFalse($link->consume());
    }

    public function test_post_after_member_deleted_redirects_to_login(): void
    {
        $member = Member::factory()->create();
        $plain = MagicLink::createForMember($member);
        $member->delete();

        $this->post('/magic-link/'.$plain)
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
