<?php

namespace Tests\Feature;

use App\Enums\CompetitionStatus;
use App\Filament\Resources\Competitions\Pages\EditCompetition;
use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Style;
use App\Notifications\CompetitionResultsNotification;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class CompetitionResultsEmailTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Style $style;

    protected function setUp(): void
    {
        parent::setUp();

        $this->style = Style::create(['code' => '1A', 'category_name' => 'SA', 'name' => 'Light Lager']);
    }

    private function competition(string $status = 'judging'): Competition
    {
        $competition = Competition::create(['name' => 'Test Cup', 'year' => 2026, 'status' => $status]);
        $competition->styles()->attach($this->style->id);

        return $competition;
    }

    private function enter(Competition $competition, Member $member, int $bottle, ?Style $style = null): void
    {
        Entry::create([
            'competition_id' => $competition->id,
            'member_id' => $member->id,
            'style_id' => ($style ?? $this->style)->id,
            'bottle_number' => $bottle,
        ]);
    }

    public function test_closing_mails_each_active_participant_once(): void
    {
        $competition = $this->competition();
        $brewer = Member::factory()->create(['locale' => 'nl']);
        $inactive = Member::factory()->inactive()->create();
        $bystander = Member::factory()->create();
        $this->enter($competition, $brewer, 1);
        $second = Style::create(['code' => '1B', 'category_name' => 'SA', 'name' => 'Pilsner']);
        $competition->styles()->attach($second->id);
        $this->enter($competition, $brewer, 2, $second);
        $this->enter($competition, $inactive, 3);
        Notification::fake();

        $competition->update(['status' => CompetitionStatus::Closed]);

        Notification::assertSentToTimes($brewer, CompetitionResultsNotification::class, 1);
        Notification::assertSentTo(
            $brewer,
            CompetitionResultsNotification::class,
            fn (CompetitionResultsNotification $n): bool => $n->locale === 'nl'
                && str_contains(implode(' ', $n->toMail($brewer)->introLines), 'Test Cup')
                && $n->toMail($brewer)->actionUrl === route('results.show', [$competition, $brewer]),
        );
        Notification::assertNotSentTo($inactive, CompetitionResultsNotification::class);
        Notification::assertNotSentTo($bystander, CompetitionResultsNotification::class);
        $this->assertNotNull($competition->fresh()->results_mailed_at);
    }

    public function test_bouncing_the_status_does_not_mail_twice(): void
    {
        $competition = $this->competition();
        $brewer = Member::factory()->create();
        $this->enter($competition, $brewer, 1);
        Notification::fake();

        $competition->update(['status' => CompetitionStatus::Closed]);
        $competition->update(['status' => CompetitionStatus::Judging]);
        $competition->update(['status' => CompetitionStatus::Closed]);

        Notification::assertSentToTimes($brewer, CompetitionResultsNotification::class, 1);
    }

    public function test_competition_already_marked_as_mailed_is_not_mailed_again(): void
    {
        $competition = $this->competition('closed');
        $competition->forceFill(['results_mailed_at' => now()->subDay()])->save();
        $brewer = Member::factory()->create();
        $this->enter($competition, $brewer, 1);
        Notification::fake();

        $competition->update(['status' => CompetitionStatus::Judging]);
        $competition->update(['status' => CompetitionStatus::Closed]);

        Notification::assertNothingSent();
    }

    public function test_resend_action_mails_only_the_chosen_member_despite_the_guard(): void
    {
        $competition = $this->competition('closed');
        $competition->forceFill(['results_mailed_at' => now()->subDay()])->save();
        $chosen = Member::factory()->create();
        $other = Member::factory()->create();
        $this->enter($competition, $chosen, 1);
        $this->enter($competition, $other, 2);
        $this->actingAs(Member::factory()->admin()->create());
        Notification::fake();

        Livewire::test(EditCompetition::class, ['record' => $competition->id])
            ->callAction(TestAction::make('resendResults'), ['member_id' => $chosen->id])
            ->assertNotified();

        Notification::assertSentToTimes($chosen, CompetitionResultsNotification::class, 1);
        Notification::assertNotSentTo($other, CompetitionResultsNotification::class);
    }

    public function test_resend_action_is_hidden_unless_closed(): void
    {
        $competition = $this->competition('judging');
        $this->actingAs(Member::factory()->admin()->create());

        Livewire::test(EditCompetition::class, ['record' => $competition->id])
            ->assertActionDoesNotExist('resendResults');
    }

    public function test_resend_does_not_offer_or_mail_inactive_participant(): void
    {
        $competition = $this->competition('closed');
        $inactive = Member::factory()->inactive()->create();
        $this->enter($competition, $inactive, 1);
        $this->actingAs(Member::factory()->admin()->create());
        Notification::fake();

        Livewire::test(EditCompetition::class, ['record' => $competition->id])
            ->callAction(TestAction::make('resendResults'), ['member_id' => $inactive->id]);

        Notification::assertNotSentTo($inactive, CompetitionResultsNotification::class);
        $this->assertArrayNotHasKey($inactive->id, $competition->activeParticipants()->pluck('name', 'id')->all());
    }

    public function test_migration_backfill_covers_closed_rows_with_null_updated_at(): void
    {
        $competition = $this->competition('closed');
        Schema::table('competitions', fn ($table) => $table->dropColumn('results_mailed_at'));
        DB::table('competitions')->where('id', $competition->id)->update(['updated_at' => null]);
        $migration = require database_path('migrations/2026_10_07_193334_add_results_mailed_at_to_competitions_table.php');
        $migration->up();

        $this->assertNotNull(DB::table('competitions')->where('id', $competition->id)->value('results_mailed_at'));
    }
}
