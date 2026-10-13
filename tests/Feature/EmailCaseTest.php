<?php

namespace Tests\Feature;

use App\Filament\Resources\Members\Pages\CreateMember;
use App\Models\Invitation;
use App\Models\Member;
use App\Notifications\MagicLinkNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class EmailCaseTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_email_is_stored_lowercased_and_trimmed(): void
    {
        $member = Member::factory()->create(['email' => ' Jan@Example.COM ']);

        $this->assertSame('jan@example.com', $member->fresh()->email);
    }

    public function test_login_ignores_capitals_in_the_typed_email(): void
    {
        $member = Member::factory()->create(['email' => 'jan@example.com', 'password' => bcrypt('secret')]);

        $this->post('/login', ['email' => 'Jan@Example.com', 'password' => 'secret'])->assertRedirect('/');

        $this->assertAuthenticatedAs($member);
    }

    public function test_guard_attempt_used_by_the_filament_login_ignores_capitals(): void
    {
        Member::factory()->admin()->create(['email' => 'admin@example.com', 'password' => bcrypt('secret')]);

        $this->assertTrue(Auth::attempt(['email' => 'ADMIN@example.com', 'password' => 'secret']));
    }

    public function test_magic_link_is_sent_when_the_typed_email_has_capitals(): void
    {
        Notification::fake();
        $member = Member::factory()->create(['email' => 'jan@example.com']);

        $this->post('/magic-link', ['email' => 'Jan@Example.com'])->assertRedirect();

        Notification::assertSentTo($member, MagicLinkNotification::class);
    }

    public function test_reset_link_and_reset_work_when_the_typed_email_has_capitals(): void
    {
        Notification::fake();
        $member = Member::factory()->create(['email' => 'jan@example.com', 'is_active' => true]);

        $this->post(route('password.email'), ['email' => 'Jan@Example.com']);
        Notification::assertSentTo($member, ResetPassword::class);

        $token = Password::broker()->createToken($member);
        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'JAN@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($member);
        $this->assertTrue(Hash::check('newpassword123', $member->fresh()->password));
    }

    public function test_registering_a_capitalised_copy_of_an_existing_email_is_refused(): void
    {
        Member::factory()->create(['email' => 'jan@example.com']);
        $invitation = Invitation::create([
            'token' => 'testtoken123',
            'type' => Invitation::TYPE_SINGLE_USE,
            'created_by' => Member::factory()->admin()->create()->id,
        ]);

        $this->post('/join/'.$invitation->token, [
            'name' => 'Jan Again',
            'email' => 'Jan@Example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertSame(1, Member::query()->where('email', 'jan@example.com')->count());
    }

    public function test_profile_cannot_take_a_capitalised_copy_of_another_members_email(): void
    {
        Member::factory()->create(['email' => 'jan@example.com']);
        $member = Member::factory()->create(['email' => 'piet@example.com']);

        $this->actingAs($member)->patch('/profile', [
            'name' => $member->name,
            'email' => 'Jan@Example.com',
            'locale' => 'en',
        ])->assertSessionHasErrors('email');

        $this->assertSame('piet@example.com', $member->fresh()->email);
    }

    public function test_admin_cannot_create_a_capitalised_copy_of_an_existing_email(): void
    {
        Member::factory()->create(['email' => 'jan@example.com']);
        $this->actingAs(Member::factory()->admin()->create());

        Livewire::test(CreateMember::class)
            ->fillForm(['name' => 'Jan Again', 'email' => 'Jan@Example.com', 'password' => 'password123', 'locale' => 'en'])
            ->call('create')
            ->assertHasFormErrors(['email']);
    }

    public function test_member_password_command_finds_a_member_by_capitalised_email(): void
    {
        Member::factory()->create(['email' => 'jan@example.com']);

        $this->artisan('member:password', ['email' => 'Jan@Example.com'])
            ->expectsQuestion('Email', 'Jan@Example.com')
            ->expectsQuestion('New password', 'newpassword123')
            ->expectsQuestion('Confirm password', 'newpassword123')
            ->assertSuccessful();
    }

    public function test_migration_lowercases_stored_emails_and_skips_case_only_duplicates(): void
    {
        DB::table('members')->insert([
            ['name' => 'A', 'email' => 'Anna@Example.com', 'password' => 'x', 'locale' => 'en'],
            ['name' => 'B', 'email' => 'Bob@Example.com', 'password' => 'x', 'locale' => 'en'],
            ['name' => 'b', 'email' => 'bob@example.com', 'password' => 'x', 'locale' => 'en'],
        ]);

        $migration = require database_path('migrations/2026_10_13_140630_lowercase_member_emails.php');
        $migration->up();

        $this->assertSame(
            ['anna@example.com', 'Bob@Example.com', 'bob@example.com'],
            DB::table('members')->orderBy('id')->pluck('email')->all(),
        );
    }
}
