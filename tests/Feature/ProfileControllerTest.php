<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_member_can_view_profile_page(): void
    {
        $member = Member::factory()->create();

        $this->actingAs($member)->get('/profile')->assertOk()->assertSee($member->name)->assertSee($member->email);
    }

    public function test_member_can_update_name_and_email(): void
    {
        $member = Member::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);

        $this->actingAs($member)->patch('/profile', [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'locale' => 'en',
        ])->assertRedirect();

        $member->refresh();
        $this->assertSame('New Name', $member->name);
        $this->assertSame('new@example.com', $member->email);
    }

    public function test_member_can_update_locale(): void
    {
        $member = Member::factory()->create(['locale' => 'en']);

        $this->actingAs($member)->patch('/profile', [
            'name' => $member->name,
            'email' => $member->email,
            'locale' => 'nl',
        ])->assertSessionHasNoErrors();

        $this->assertSame('nl', $member->fresh()->locale);
    }

    public function test_locale_must_be_valid(): void
    {
        $member = Member::factory()->create();

        $this->actingAs($member)->patch('/profile', [
            'name' => $member->name,
            'email' => $member->email,
            'locale' => 'fr',
        ])->assertSessionHasErrors('locale');
    }

    public function test_name_is_required(): void
    {
        $member = Member::factory()->create();

        $this->actingAs($member)->patch('/profile', [
            'name' => '',
            'email' => $member->email,
            'locale' => 'en',
        ])->assertSessionHasErrors('name');
    }

    public function test_email_must_be_unique_among_other_members(): void
    {
        $other = Member::factory()->create(['email' => 'taken@example.com']);
        $member = Member::factory()->create();

        $this->actingAs($member)->patch('/profile', [
            'name' => $member->name,
            'email' => 'taken@example.com',
            'locale' => 'en',
        ])->assertSessionHasErrors('email');
    }

    public function test_member_can_keep_own_email(): void
    {
        $member = Member::factory()->create(['email' => 'mine@example.com']);

        $this->actingAs($member)->patch('/profile', [
            'name' => $member->name,
            'email' => 'mine@example.com',
            'locale' => 'en',
        ])->assertSessionHasNoErrors();
    }

    public function test_member_can_change_password_with_correct_current_password(): void
    {
        $member = Member::factory()->create(['password' => Hash::make('old-secret')]);

        $this->actingAs($member)->patch('/profile', [
            'name' => $member->name,
            'email' => $member->email,
            'locale' => 'en',
            'current_password' => 'old-secret',
            'password' => 'new-secret-1',
            'password_confirmation' => 'new-secret-1',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('new-secret-1', $member->fresh()->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $member = Member::factory()->create(['password' => Hash::make('correct-password')]);

        $this->actingAs($member)->patch('/profile', [
            'name' => $member->name,
            'email' => $member->email,
            'locale' => 'en',
            'current_password' => 'wrong-password',
            'password' => 'new-secret-1',
            'password_confirmation' => 'new-secret-1',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('correct-password', $member->fresh()->password));
    }

    public function test_password_is_not_changed_when_fields_are_blank(): void
    {
        $member = Member::factory()->create(['password' => Hash::make('keep-this')]);

        $this->actingAs($member)->patch('/profile', [
            'name' => $member->name,
            'email' => $member->email,
            'locale' => 'en',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('keep-this', $member->fresh()->password));
    }
}
