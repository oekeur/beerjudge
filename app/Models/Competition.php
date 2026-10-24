<?php

namespace App\Models;

use App\Enums\CompetitionStatus;
use App\Notifications\CompetitionResultsNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property int $year
 * @property CompetitionStatus $status
 * @property Carbon|null $judging_date
 * @property Carbon|null $results_mailed_at
 * @property Collection<int, Member> $judges
 * @property Collection<int, Entry> $entries
 */
class Competition extends Model
{
    protected $attributes = ['status' => 'draft'];

    protected $fillable = [
        'name', 'year', 'judging_date',
    ];

    protected function casts(): array
    {
        return [
            'judging_date' => 'date',
            'results_mailed_at' => 'datetime',
            'status' => CompetitionStatus::class,
        ];
    }

    /**
     * Sets the lifecycle status. Status is not mass assignable; this is the only writer.
     * It saves through the model, so the `updated` hook that mails results still fires.
     * It applies no lifecycle rules: those live in the EditCompetition actions.
     */
    public function transitionTo(CompetitionStatus $status): void
    {
        $this->forceFill(['status' => $status])->save();
    }

    /**
     * Mails every participant their results link the first time the competition is closed.
     * The conditional update claims the send atomically, so a status bounce or a concurrent
     * save cannot trigger a second bulk send.
     */
    protected static function booted(): void
    {
        static::updated(function (Competition $competition): void {
            if (! $competition->wasChanged('status') || ! $competition->isClosed()) {
                return;
            }

            $claimed = static::query()
                ->whereKey($competition->getKey())
                ->whereNull('results_mailed_at')
                ->update(['results_mailed_at' => now()]);

            if ($claimed === 1) {
                $competition->sendResultsEmails();
            }
        });
    }

    /**
     * Sends the results email to each active member with at least one entry, or to the given members only.
     *
     * @param  Collection<int, Member>|null  $members
     */
    public function sendResultsEmails(?Collection $members = null): void
    {
        $members ??= $this->activeParticipants()->get();

        foreach ($members as $member) {
            $member->notify((new CompetitionResultsNotification($this))->locale($member->locale));
        }
    }

    /**
     * @return Builder<Member>
     */
    public function participants(): Builder
    {
        return Member::query()->whereHas('entries', fn (Builder $query) => $query->where('competition_id', $this->id));
    }

    /**
     * Participants who can open the login-gated results link (RequireActiveMember bounces the rest).
     *
     * @return Builder<Member>
     */
    public function activeParticipants(): Builder
    {
        return $this->participants()->where('is_active', true);
    }

    public function isDraft(): bool
    {
        return $this->status === CompetitionStatus::Draft;
    }

    public function isOpen(): bool
    {
        return $this->status === CompetitionStatus::Open;
    }

    public function isJudging(): bool
    {
        return $this->status === CompetitionStatus::Judging;
    }

    public function isClosed(): bool
    {
        return $this->status === CompetitionStatus::Closed;
    }

    /**
     * Entries are fixed from Judging on: bottle numbers are assigned and judges score against them.
     */
    public function entriesAreLocked(): bool
    {
        return $this->isJudging() || $this->isClosed();
    }

    /**
     * @return BelongsToMany<Style, $this>
     */
    public function styles(): BelongsToMany
    {
        return $this->belongsToMany(Style::class, 'competition_style');
    }

    /**
     * @return BelongsToMany<Member, $this>
     */
    public function judges(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'competition_judge');
    }

    /**
     * @return HasMany<Entry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }
}
