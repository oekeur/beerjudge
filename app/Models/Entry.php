<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $competition_id
 * @property int $member_id
 * @property int $style_id
 * @property int|null $bottle_number
 * @property int|null $sort_order
 * @property string|null $brewer_notes
 * @property-read float|null $avg_total
 * @property-read int|null $sheet_count
 * @property-read float|null $max_total
 * @property-read float|null $min_total
 */
class Entry extends Model
{
    protected $fillable = [
        'competition_id', 'member_id', 'style_id', 'bottle_number', 'sort_order', 'brewer_notes',
    ];

    protected static function booted(): void
    {
        static::saving(function (Entry $entry): void {
            $inCompetition = \DB::table('competition_style')
                ->where('competition_id', $entry->competition_id)
                ->where('style_id', $entry->style_id)
                ->exists();

            if (! $inCompetition) {
                throw new \InvalidArgumentException("Style {$entry->style_id} is not part of this competition.");
            }
        });
    }

    /**
     * @return BelongsTo<Competition, $this>
     */
    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return BelongsTo<Style, $this>
     */
    public function style(): BelongsTo
    {
        return $this->belongsTo(Style::class);
    }

    /**
     * @return HasMany<Scoresheet, $this>
     */
    public function scoresheets(): HasMany
    {
        return $this->hasMany(Scoresheet::class);
    }
}
