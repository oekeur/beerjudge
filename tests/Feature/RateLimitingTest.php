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
}
