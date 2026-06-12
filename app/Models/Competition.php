<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $judging_date
 */
class Competition extends Model
{
    const STATUS_DRAFT = 'draft';

    const STATUS_OPEN = 'open';

    const STATUS_JUDGING = 'judging';

    const STATUS_CLOSED = 'closed';

    protected $attributes = ['status' => self::STATUS_DRAFT];

    protected $fillable = [
        'name', 'year', 'judging_date', 'status',
    ];

    protected function casts(): array
    {
        return [
            'judging_date' => 'date',
        ];
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
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
