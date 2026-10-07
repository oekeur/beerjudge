<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GuestLocaleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_failed_login_shows_dutch_error_for_dutch_browser(): void
    {
        $member = Member::factory()->create();

        $this->withHeaders(['Accept-Language' => 'nl'])->from('/login')->followingRedirects()
            ->post('/login', ['email' => $member->email, 'password' => 'wrong'])
            ->assertSee('Ongeldige inloggegevens.');
    }

    public function test_failed_login_shows_english_error_without_accept_language(): void
    {
        $member = Member::factory()->create();

        $this->from('/login')->followingRedirects()
            ->post('/login', ['email' => $member->email, 'password' => 'wrong'])
            ->assertSee('Invalid credentials.');
    }

    public function test_regional_dutch_variant_resolves_to_dutch(): void
    {
        $this->withHeaders(['Accept-Language' => 'nl-BE'])->get('/login')->assertOk();

        $this->assertSame('nl', app()->getLocale());
    }

    public function test_unsupported_language_falls_back_to_app_default(): void
    {
        $this->withHeaders(['Accept-Language' => 'de-DE,de;q=0.9'])->get('/login');

        $this->assertSame(config('app.locale'), app()->getLocale());
    }

    public function test_missing_header_uses_app_default(): void
    {
        $this->get('/login');

        $this->assertSame(config('app.locale'), app()->getLocale());
    }

    public function test_guest_locale_does_not_leak_from_previous_request(): void
    {
        $this->withHeaders(['Accept-Language' => 'nl'])->get('/login');
        $this->assertSame('nl', app()->getLocale());

        $this->withHeaders(['Accept-Language' => '*'])->get('/login');
        $this->assertSame('en', app()->getLocale());

        $this->withHeaders(['Accept-Language' => 'nl'])->get('/login');
        $this->withoutHeader('Accept-Language')->get('/login');
        $this->assertSame('en', app()->getLocale());
    }

    public function test_member_profile_locale_wins_over_accept_language(): void
    {
        $member = Member::factory()->create(['locale' => 'nl']);

        $this->actingAs($member)->withHeaders(['Accept-Language' => 'en-US'])->get('/profile');
        $this->assertSame('nl', app()->getLocale());

        $this->withHeaders(['Accept-Language' => 'de'])->get('/profile');
        $this->assertSame('nl', app()->getLocale());
    }

    public function test_english_member_keeps_english_despite_dutch_header(): void
    {
        $member = Member::factory()->create(['locale' => 'en']);

        $this->actingAs($member)->withHeaders(['Accept-Language' => 'nl'])->get('/profile');

        $this->assertSame('en', app()->getLocale());
    }

    public function test_magic_link_mail_follows_member_profile_not_browser(): void
    {
        $member = Member::factory()->create(['locale' => 'en']);

        $this->withHeaders(['Accept-Language' => 'nl'])->post('/magic-link', ['email' => $member->email])->assertRedirect();

        $this->assertSame('Your login link', $this->sentSubject());
    }

    public function test_magic_link_mail_in_dutch_for_dutch_member_with_english_browser(): void
    {
        $member = Member::factory()->create(['locale' => 'nl']);

        $this->withHeaders(['Accept-Language' => 'en'])->post('/magic-link', ['email' => $member->email])->assertRedirect();

        $this->assertSame('Je inloglink', $this->sentSubject());
    }

    private function sentSubject(): ?string
    {
        $transport = Mail::mailer()->getSymfonyTransport();
        $this->assertInstanceOf(ArrayTransport::class, $transport);

        $messages = $transport->messages();
        $this->assertCount(1, $messages);

        return $messages->first()->getOriginalMessage()->getSubject();
    }
}
