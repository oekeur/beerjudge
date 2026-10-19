<?php

namespace Tests\Feature;

use App\Models\MagicLink;
use App\Models\Member;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_one_magic_link_is_throttled_after_five_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->get('/magic-link/sametoken');
        }

        $this->get('/magic-link/sametoken')->assertStatus(429);
        $this->assertNotSame(429, $this->get('/magic-link/othertoken')->status());
    }

    public function test_one_magic_link_shares_its_limit_of_five_across_get_and_post(): void
    {
        $this->get('/magic-link/sametoken');
        $this->post('/magic-link/sametoken');
        $this->get('/magic-link/sametoken');
        $this->post('/magic-link/sametoken');
        $this->get('/magic-link/sametoken');

        $this->post('/magic-link/sametoken')->assertStatus(429);
    }

    public function test_sixty_first_magic_link_attempt_from_one_ip_is_throttled_whatever_the_token(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->assertNotSame(429, $this->get('/magic-link/guess'.$i)->status());
        }

        $this->get('/magic-link/guess60')->assertStatus(429);
    }

    public function test_fifteen_members_on_one_ip_can_each_request_and_open_a_magic_link(): void
    {
        Notification::fake();

        foreach (Member::factory()->count(15)->create(['is_active' => true]) as $member) {
            $this->post('/magic-link', ['email' => $member->email])->assertRedirect();

            $plain = MagicLink::createForMember($member);
            $this->get('/magic-link/'.$plain)->assertOk();
            $this->post('/magic-link/'.$plain)->assertRedirect('/');

            $this->post('/logout');
            $this->flushSession();
        }
    }

    public function test_fourth_magic_link_request_for_one_email_within_five_minutes_is_throttled(): void
    {
        Notification::fake();
        $member = Member::factory()->create(['is_active' => true]);

        for ($i = 0; $i < 3; $i++) {
            $this->post('/magic-link', ['email' => $member->email])->assertRedirect();
        }

        $this->post('/magic-link', ['email' => strtoupper($member->email)])->assertStatus(429);
        $this->post('/magic-link', ['email' => 'someone-else@example.com'])->assertRedirect();
    }

    public function test_forty_sixth_magic_link_request_from_one_ip_is_throttled_whatever_the_email(): void
    {
        Notification::fake();

        for ($i = 0; $i < 45; $i++) {
            $this->post('/magic-link', ['email' => "user{$i}@example.com"])->assertRedirect();
        }

        $this->post('/magic-link', ['email' => 'user45@example.com'])->assertStatus(429);
    }

    public function test_full_magic_link_login_does_not_use_up_the_link_request_limit_for_the_next_member(): void
    {
        Notification::fake();

        foreach ([Member::factory()->create(), Member::factory()->create()] as $member) {
            $this->post('/magic-link', ['email' => $member->email])->assertRedirect();

            $plain = MagicLink::createForMember($member);
            $this->get('/magic-link/'.$plain)->assertOk();
            $this->post('/magic-link/'.$plain)->assertRedirect('/');

            $this->post('/logout');
            $this->flushSession();
        }
    }

    public function test_password_reset_confirm_for_one_email_throttled_after_five_attempts(): void
    {
        $member = Member::factory()->create(['is_active' => true]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('password.update'), [
                'token' => 'invalid-token',
                'email' => $member->email,
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);
        }

        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $member->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertStatus(429);

        $other = Member::factory()->create(['is_active' => true]);
        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $other->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertStatus(302);
    }

    public function test_ten_members_can_log_in_from_one_ip_within_a_minute(): void
    {
        foreach (Member::factory()->count(10)->create() as $member) {
            $this->post('/login', ['email' => $member->email, 'password' => 'password'])
                ->assertRedirect('/');
            $this->assertAuthenticatedAs($member);
            $this->post('/logout');
            $this->flushSession();
        }
    }

    public function test_seventh_failed_login_for_one_email_is_throttled(): void
    {
        $member = Member::factory()->create();

        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', ['email' => $member->email, 'password' => 'wrong'])->assertStatus(302);
        }

        $this->post('/login', ['email' => strtoupper($member->email), 'password' => 'wrong'])->assertStatus(429);
        $this->post('/login', ['email' => 'other@example.com', 'password' => 'wrong'])
            ->assertRedirect()
            ->assertSessionHasErrors('email');
    }

    public function test_thirty_first_login_from_one_ip_is_throttled_whatever_the_email(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->post('/login', ['email' => "user{$i}@example.com", 'password' => 'wrong'])->assertStatus(302);
        }

        $this->post('/login', ['email' => 'user31@example.com', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_login_attempts_do_not_count_toward_magic_link_request_or_reset_confirm_limits(): void
    {
        Notification::fake();
        $member = Member::factory()->create();

        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', ['email' => $member->email, 'password' => 'wrong']);
        }

        $this->post('/magic-link', ['email' => $member->email])->assertStatus(302);
        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $member->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertStatus(302);
    }

    public function test_fourth_password_reset_request_for_one_email_within_five_minutes_is_throttled(): void
    {
        Notification::fake();
        $member = Member::factory()->create(['is_active' => true]);

        for ($i = 0; $i < 3; $i++) {
            $this->post(route('password.email'), ['email' => $member->email])->assertRedirect();
        }

        $this->post(route('password.email'), ['email' => strtoupper($member->email)])->assertStatus(429);
        $this->post(route('password.email'), ['email' => 'someone-else@example.com'])->assertRedirect();
    }

    public function test_forty_sixth_password_reset_request_from_one_ip_is_throttled_whatever_the_email(): void
    {
        Notification::fake();

        for ($i = 0; $i < 45; $i++) {
            $this->post(route('password.email'), ['email' => "user{$i}@example.com"])->assertRedirect();
        }

        $this->post(route('password.email'), ['email' => 'user45@example.com'])->assertStatus(429);
    }

    public function test_password_reset_request_answers_the_same_for_member_and_unknown_address_including_when_throttled(): void
    {
        Notification::fake();
        $member = Member::factory()->create(['is_active' => true]);
        $unknown = 'nobody@example.com';

        for ($i = 0; $i < 3; $i++) {
            $memberResponse = $this->post(route('password.email'), ['email' => $member->email]);
            $memberFlash = session('success');
            $unknownResponse = $this->post(route('password.email'), ['email' => $unknown]);

            $this->assertSame($memberResponse->getStatusCode(), $unknownResponse->getStatusCode());
            $this->assertSame($memberResponse->headers->get('Location'), $unknownResponse->headers->get('Location'));
            $this->assertSame($memberFlash, session('success'));
            $this->assertSame(__('passwords.sent'), session('success'));
        }

        $memberThrottled = $this->post(route('password.email'), ['email' => $member->email]);
        $unknownThrottled = $this->post(route('password.email'), ['email' => $unknown]);

        $memberThrottled->assertStatus(429);
        $unknownThrottled->assertStatus(429);
        $this->assertSame($memberThrottled->getContent(), $unknownThrottled->getContent());

        Notification::assertSentToTimes($member, ResetPassword::class, 1);
        Notification::assertNothingSentTo(Member::factory()->make(['email' => $unknown]));
    }

    public function test_password_reset_requests_do_not_count_toward_magic_link_request_or_reset_confirm_limits(): void
    {
        Notification::fake();
        $member = Member::factory()->create(['is_active' => true]);

        for ($i = 0; $i < 3; $i++) {
            $this->post(route('password.email'), ['email' => $member->email]);
        }

        $this->post('/magic-link', ['email' => $member->email])->assertStatus(302);
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('password.update'), [
                'token' => 'invalid-token',
                'email' => $member->email,
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])->assertStatus(302);
        }
    }

    public function test_login_with_array_email_does_not_error(): void
    {
        $this->post('/login', ['email' => ['x'], 'password' => 'wrong'])->assertStatus(302);
    }
}
