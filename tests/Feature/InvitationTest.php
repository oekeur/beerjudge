<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function makeAdmin(): Member
    {
        return Member::factory()->admin()->create();
    }

    private function createInvitation(array $overrides = []): Invitation
    {
        $admin = $this->makeAdmin();

        return Invitation::create(array_merge([
            'token' => 'testtoken123',
            'type' => Invitation::TYPE_SINGLE_USE,
            'created_by' => $admin->id,
        ], $overrides));
    }

    public function test_single_use_token_shows_registration_form(): void
    {
        $invitation = $this->createInvitation();

        $this->get('/join/'.$invitation->token)->assertOk();
    }

    public function test_used_single_use_token_returns_410(): void
    {
        $invitation = $this->createInvitation(['used_at' => now()]);

        $this->get('/join/'.$invitation->token)->assertStatus(410);
    }

    public function test_expired_token_returns_410(): void
    {
        $invitation = $this->createInvitation([
            'type' => Invitation::TYPE_TIME_BOUND,
            'expires_at' => Carbon::yesterday(),
        ]);

        $this->get('/join/'.$invitation->token)->assertStatus(410);
    }

    public function test_unknown_token_returns_404(): void
    {
        $this->get('/join/doesnotexist')->assertNotFound();
    }

    public function test_registration_creates_inactive_member(): void
    {
        $invitation = $this->createInvitation();

        $this->post('/join/'.$invitation->token, [
            'name' => 'New Member',
            'email' => 'newmember@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('login'));

        $member = Member::where('email', 'newmember@example.com')->first();
        $this->assertNotNull($member);
        $this->assertFalse($member->is_active);
    }

    public function test_registration_validates_required_fields(): void
    {
        $invitation = $this->createInvitation();

        $this->post('/join/'.$invitation->token, [])
            ->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_duplicate_email_rejected(): void
    {
        Member::factory()->create(['email' => 'taken@example.com']);
        $invitation = $this->createInvitation();

        $this->post('/join/'.$invitation->token, [
            'name' => 'Someone',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors(['email']);
    }

    public function test_single_use_token_is_invalidated_after_registration(): void
    {
        $invitation = $this->createInvitation();

        $this->post('/join/'.$invitation->token, [
            'name' => 'New Member',
            'email' => 'newmember@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->get('/join/'.$invitation->token)->assertStatus(410);
    }

    public function test_time_bound_token_not_invalidated_after_first_use(): void
    {
        $invitation = $this->createInvitation([
            'type' => Invitation::TYPE_TIME_BOUND,
            'expires_at' => Carbon::tomorrow(),
        ]);

        $this->post('/join/'.$invitation->token, [
            'name' => 'First Member',
            'email' => 'first@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->get('/join/'.$invitation->token)->assertOk();
    }

    public function test_time_bound_token_usable_before_expiry(): void
    {
        $invitation = $this->createInvitation([
            'type' => Invitation::TYPE_TIME_BOUND,
            'expires_at' => Carbon::tomorrow(),
        ]);

        $this->post('/join/'.$invitation->token, [
            'name' => 'New Member',
            'email' => 'newmember@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('members', ['email' => 'newmember@example.com', 'is_active' => false]);
    }
}
