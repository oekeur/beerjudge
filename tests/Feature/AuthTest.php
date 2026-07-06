<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_page_is_accessible_to_guests(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_valid_credentials_log_member_in_and_redirect_to_home(): void
    {
        $member = Member::factory()->create(['email' => 'member@test.com', 'password' => bcrypt('secret')]);

        $this->post('/login', ['email' => 'member@test.com', 'password' => 'secret'])
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($member);
        $this->assertNotNull($member->fresh()->remember_token);
    }

    public function test_admin_login_redirects_to_admin(): void
    {
        Member::factory()->create(['email' => 'admin@test.com', 'password' => bcrypt('secret'), 'is_admin' => true]);

        $this->post('/login', ['email' => 'admin@test.com', 'password' => 'secret'])
            ->assertRedirect('/admin');
    }

    public function test_wrong_password_returns_validation_error(): void
    {
        Member::factory()->create(['email' => 'member2@test.com', 'password' => bcrypt('correct')]);

        $this->post('/login', ['email' => 'member2@test.com', 'password' => 'wrong'])
            ->assertSessionHasErrors(['email']);

        $this->assertGuest();
    }

    public function test_unknown_email_returns_validation_error(): void
    {
        $this->post('/login', ['email' => 'nobody@test.com', 'password' => 'any'])
            ->assertSessionHasErrors(['email']);

        $this->assertGuest();
    }

    public function test_logout_clears_session_and_redirects_to_login(): void
    {
        $member = Member::factory()->create();

        $this->actingAs($member)->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_unauthenticated_logout_redirects_to_login(): void
    {
        $this->post('/logout')->assertRedirect('/login');
    }
}
