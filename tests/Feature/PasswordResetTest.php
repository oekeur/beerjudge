<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_form_is_accessible(): void
    {
        $this->get(route('password.request'))->assertOk();
    }

    public function test_reset_link_sent_to_active_member(): void
    {
        Notification::fake();

        $member = Member::factory()->create(['is_active' => true]);

        $this->post(route('password.email'), ['email' => $member->email])
            ->assertRedirect()
            ->assertSessionHas('success');

        Notification::assertSentTo($member, ResetPassword::class);
    }

    public function test_reset_link_not_sent_to_inactive_member(): void
    {
        Notification::fake();

        $member = Member::factory()->create(['is_active' => false]);

        $this->post(route('password.email'), ['email' => $member->email])
            ->assertRedirect()
            ->assertSessionHas('success');

        Notification::assertNothingSent();
    }

    public function test_reset_link_not_sent_to_unknown_email(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'nobody@example.com'])
            ->assertRedirect()
            ->assertSessionHas('success');

        Notification::assertNothingSent();
    }

    public function test_reset_form_is_accessible(): void
    {
        $member = Member::factory()->create(['is_active' => true]);
        $token = Password::broker()->createToken($member);

        $this->get(route('password.reset', ['token' => $token, 'email' => $member->email]))
            ->assertOk();
    }

    public function test_password_is_reset_with_valid_token(): void
    {
        $member = Member::factory()->create(['is_active' => true]);
        $token = Password::broker()->createToken($member);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $member->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($member);
        $this->assertTrue(Hash::check('newpassword123', $member->fresh()->password));
    }

    public function test_reset_fails_with_invalid_token(): void
    {
        $member = Member::factory()->create(['is_active' => true]);

        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $member->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
            ->assertRedirect()
            ->assertSessionHasErrors(['email']);
    }

    public function test_reset_fails_with_mismatched_confirmation(): void
    {
        $member = Member::factory()->create(['is_active' => true]);
        $token = Password::broker()->createToken($member);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $member->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ])
            ->assertSessionHasErrors(['password']);
    }
}
