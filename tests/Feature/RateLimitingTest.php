<?php

namespace Tests\Feature;

use App\Models\MagicLink;
use App\Models\Member;
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

    public function test_magic_link_verify_throttled_after_five_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->get('/magic-link/bogustoken'.$i);
        }

        $this->get('/magic-link/bogustoken5')->assertStatus(429);
    }

    public function test_magic_link_token_routes_share_a_limit_of_five_across_get_and_post(): void
    {
        $this->get('/magic-link/bogus1');
        $this->post('/magic-link/bogus2');
        $this->get('/magic-link/bogus3');
        $this->post('/magic-link/bogus4');
        $this->get('/magic-link/bogus5');

        $this->post('/magic-link/bogus6')->assertStatus(429);
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

    public function test_password_reset_confirm_throttled_after_five_attempts(): void
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

    public function test_login_with_array_email_does_not_error(): void
    {
        $this->post('/login', ['email' => ['x'], 'password' => 'wrong'])->assertStatus(302);
    }
}
