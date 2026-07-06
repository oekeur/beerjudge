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

    public function test_valid_token_logs_member_in(): void
    {
        $member = Member::factory()->create();
        $plain = MagicLink::createForMember($member);

        $this->get('/magic-link/'.$plain)
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($member);
        $this->assertNotNull($member->fresh()->remember_token);
    }

    public function test_expired_magic_link_redirects_with_error(): void
    {
        $member = Member::factory()->create();
        $plain = MagicLink::createForMember($member);

        MagicLink::where('member_id', $member->id)->update(['expires_at' => now()->subMinute()]);

        $this->get('/magic-link/'.$plain)
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_used_magic_link_is_rejected(): void
    {
        $member = Member::factory()->create();
        $plain = MagicLink::createForMember($member);

        $this->get('/magic-link/'.$plain);
        $this->post('/logout');

        $this->get('/magic-link/'.$plain)
            ->assertRedirect(route('login'));
    }

    public function test_invalid_token_redirects_to_login(): void
    {
        $this->get('/magic-link/totallybogustoken')
            ->assertRedirect(route('login'));
    }
}
