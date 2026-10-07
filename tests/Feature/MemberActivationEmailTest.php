<?php

namespace Tests\Feature;

use App\Filament\Resources\Members\Pages\ListMembers;
use App\Models\Member;
use App\Notifications\MemberActivatedNotification;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class MemberActivationEmailTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_activating_a_member_sends_the_email_in_their_locale(): void
    {
        $member = Member::factory()->inactive()->create(['locale' => 'nl']);
        Notification::fake();

        $member->update(['is_active' => true]);

        Notification::assertSentTo(
            $member,
            MemberActivatedNotification::class,
            fn (MemberActivatedNotification $notification): bool => $notification->locale === 'nl',
        );
        Notification::assertCount(1);
    }

    public function test_email_links_to_login(): void
    {
        $member = Member::factory()->create();

        $mail = (new MemberActivatedNotification)->toMail($member);

        $this->assertSame(route('login'), $mail->actionUrl);
    }

    public function test_saving_an_active_member_without_activation_sends_nothing(): void
    {
        $member = Member::factory()->create();
        Notification::fake();

        $member->update(['name' => 'Renamed']);
        $member->update(['is_active' => true]);

        Notification::assertNothingSent();
    }

    public function test_deactivating_a_member_sends_nothing(): void
    {
        $member = Member::factory()->create();
        Notification::fake();

        $member->update(['is_active' => false]);

        Notification::assertNothingSent();
    }

    public function test_creating_an_active_member_sends_nothing(): void
    {
        Notification::fake();

        Member::factory()->create();

        Notification::assertNothingSent();
    }

    public function test_registration_sends_nothing_until_approved(): void
    {
        Notification::fake();

        Member::factory()->inactive()->create();

        Notification::assertNothingSent();
    }

    public function test_approve_table_action_sends_the_email(): void
    {
        $member = Member::factory()->inactive()->create();
        $this->actingAs(Member::factory()->admin()->create());
        Notification::fake();

        Livewire::test(ListMembers::class)
            ->callAction(TestAction::make('approve')->table($member));

        Notification::assertSentTo($member, MemberActivatedNotification::class);
    }

    public function test_bulk_approve_sends_one_email_per_member(): void
    {
        $members = Member::factory()->inactive()->count(2)->create();
        $this->actingAs(Member::factory()->admin()->create());
        Notification::fake();

        Livewire::test(ListMembers::class)
            ->selectTableRecords($members->modelKeys())
            ->callAction(TestAction::make('approve')->table()->bulk());

        Notification::assertSentTo($members, MemberActivatedNotification::class);
        Notification::assertCount(2);
    }
}
