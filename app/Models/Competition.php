<?php

namespace App\Models;

use App\Enums\CompetitionStatus;
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
 * @property Collection<int, Member> $judges
 * @property Collection<int, Entry> $entries
 */
class Competition extends Model
{
    protected $attributes = ['status' => 'draft'];

    protected $fillable = [
        'name', 'year', 'judging_date', 'status',
    ];

    protected function casts(): array
    {
        return [
            'judging_date' => 'date',
            'status' => CompetitionStatus::class,
        ];
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
