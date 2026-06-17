<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
