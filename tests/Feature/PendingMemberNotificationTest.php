<?php

namespace Tests\Feature;

use App\Filament\Resources\Members\Pages\CreateMember;
use App\Filament\Resources\Members\Pages\ListMembers;
use App\Models\Invitation;
use App\Models\Member;
use App\Notifications\PendingMemberNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class PendingMemberNotificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const ADMIN_ADDRESS = 'notify@example.com';

    private function createInvitation(array $overrides = []): Invitation
    {
        return Invitation::create(array_merge([
            'token' => 'testtoken123',
            'type' => Invitation::TYPE_SINGLE_USE,
            'created_by' => Member::factory()->admin()->create()->id,
        ], $overrides));
    }

    /**
     * @param  array<string, string>  $headers
     * @return TestResponse<Response>
     */
    private function register(Invitation $invitation, array $headers = []): TestResponse
    {
        return $this->withHeaders($headers)->post('/join/'.$invitation->token, [
            'name' => 'New Member',
            'email' => 'newmember@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
    }

    private function sentNotification(): PendingMemberNotification
    {
        $sent = null;

        Notification::assertSentOnDemand(
            PendingMemberNotification::class,
            function (PendingMemberNotification $notification, array $channels, AnonymousNotifiable $notifiable) use (&$sent): bool {
                $sent = $notification;

                return $notifiable->routeNotificationFor('mail') === self::ADMIN_ADDRESS;
            },
        );
        Notification::assertCount(1);

        $this->assertInstanceOf(PendingMemberNotification::class, $sent);

        return $sent;
    }

    public function test_invite_registration_mails_the_admin_notification_address(): void
    {
        config(['app.admin_notification_email' => self::ADMIN_ADDRESS]);
        $invitation = $this->createInvitation();
        Notification::fake();

        $this->register($invitation)->assertRedirect(route('login'));

        $notification = $this->sentNotification();
        $member = Member::where('email', 'newmember@example.com')->sole();
        $this->assertTrue($notification->member->is($member));
    }

    public function test_mail_names_the_member_and_links_to_the_pending_filter(): void
    {
        $member = Member::factory()->inactive()->create(['name' => 'New Member', 'email' => 'newmember@example.com']);

        $mail = (new PendingMemberNotification($member))->toMail(new AnonymousNotifiable);
        $html = (string) $mail->render();

        $this->assertStringContainsString('New Member', $html);
        $this->assertStringContainsString('newmember@example.com', $html);
        $this->assertStringContainsString('filters[pending_approval][isActive]=1', urldecode((string) $mail->actionUrl));
        $this->assertStringStartsWith(url('/admin/members'), (string) $mail->actionUrl);
    }

    public function test_the_mailed_link_lists_inactive_members_only(): void
    {
        $pending = Member::factory()->inactive()->create();
        $active = Member::factory()->create();
        $this->actingAs(Member::factory()->admin()->create());

        $query = parse_url(PendingMemberNotification::pendingMembersUrl(), PHP_URL_QUERY);
        $this->assertIsString($query);
        parse_str($query, $parameters);

        Livewire::withQueryParams($parameters)
            ->test(ListMembers::class)
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$active]);
    }

    public function test_unset_address_sends_nothing_and_still_registers(): void
    {
        config(['app.admin_notification_email' => null]);
        $invitation = $this->createInvitation();
        Notification::fake();

        $this->register($invitation)->assertRedirect(route('login'));

        Notification::assertNothingSent();
        $this->assertFalse(Member::where('email', 'newmember@example.com')->sole()->is_active);
    }

    public function test_blank_address_sends_nothing_and_still_registers(): void
    {
        config(['app.admin_notification_email' => '']);
        $invitation = $this->createInvitation();
        Notification::fake();

        $this->register($invitation)->assertRedirect(route('login'));

        Notification::assertNothingSent();
        $this->assertFalse(Member::where('email', 'newmember@example.com')->sole()->is_active);
    }

    public function test_used_invitation_sends_nothing(): void
    {
        config(['app.admin_notification_email' => self::ADMIN_ADDRESS]);
        $invitation = $this->createInvitation(['used_at' => now()]);
        Notification::fake();

        $this->register($invitation)->assertStatus(410);

        Notification::assertNothingSent();
    }

    public function test_unknown_invitation_sends_nothing(): void
    {
        config(['app.admin_notification_email' => self::ADMIN_ADDRESS]);
        Notification::fake();

        $this->register(new Invitation(['token' => 'no-such-token']))->assertNotFound();

        Notification::assertNothingSent();
    }

    public function test_admin_creating_an_inactive_member_sends_nothing(): void
    {
        config(['app.admin_notification_email' => self::ADMIN_ADDRESS]);
        $this->actingAs(Member::factory()->admin()->create());
        Notification::fake();

        Livewire::test(CreateMember::class)
            ->fillForm([
                'name' => 'Added By Admin',
                'email' => 'added@example.com',
                'password' => 'password123',
                'locale' => 'en',
                'is_active' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertFalse(Member::where('email', 'added@example.com')->sole()->is_active);
        Notification::assertNothingSent();
    }

    public function test_mail_ignores_the_registrants_browser_language(): void
    {
        config(['app.admin_notification_email' => self::ADMIN_ADDRESS]);
        $invitation = $this->createInvitation();
        Notification::fake();

        $this->register($invitation, ['Accept-Language' => 'nl'])->assertRedirect(route('login'));

        $notification = $this->sentNotification();
        $this->assertSame('en', $notification->locale);
    }

    public function test_mail_uses_the_locale_of_the_member_owning_the_address(): void
    {
        config(['app.admin_notification_email' => 'Notify@Example.com']);
        Member::factory()->admin()->create(['email' => self::ADMIN_ADDRESS, 'locale' => 'nl']);
        $invitation = $this->createInvitation();
        Notification::fake();

        $this->register($invitation, ['Accept-Language' => 'en'])->assertRedirect(route('login'));

        $sent = null;
        Notification::assertSentOnDemand(
            PendingMemberNotification::class,
            function (PendingMemberNotification $notification) use (&$sent): bool {
                $sent = $notification;

                return true;
            },
        );
        $this->assertInstanceOf(PendingMemberNotification::class, $sent);
        $this->assertSame('nl', $sent->locale);
    }

    public function test_queued_mail_renders_in_the_chosen_locale(): void
    {
        config(['app.admin_notification_email' => self::ADMIN_ADDRESS]);
        Member::factory()->admin()->create(['email' => self::ADMIN_ADDRESS, 'locale' => 'nl']);
        $invitation = $this->createInvitation();
        $sentMails = [];
        $this->app['events']->listen(
            MessageSending::class,
            function (MessageSending $event) use (&$sentMails): void {
                $sentMails[] = $event->message;
            },
        );

        $this->register($invitation, ['Accept-Language' => 'en'])->assertRedirect(route('login'));

        $this->assertCount(1, $sentMails);
        $this->assertSame('Nieuw lid wacht op goedkeuring', $sentMails[0]->getSubject());
    }

    public function test_failed_dispatch_is_reported_and_registration_still_succeeds(): void
    {
        config(['app.admin_notification_email' => self::ADMIN_ADDRESS]);
        $invitation = $this->createInvitation();
        Exceptions::fake();
        Notification::shouldReceive('send')->andThrow(new RuntimeException('mail transport down'));

        $this->register($invitation)
            ->assertRedirect(route('login'))
            ->assertSessionHas('success', __('auth.register_success'));

        Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'mail transport down');
        $this->assertNotNull(Member::where('email', 'newmember@example.com')->first());
    }
}
