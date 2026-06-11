<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entry extends Model
{
    protected $fillable = [
        'competition_id', 'member_id', 'style_id', 'bottle_number', 'sort_order', 'brewer_notes',
    ];

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
