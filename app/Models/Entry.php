<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entry extends Model
{
    protected $fillable = [
        'competition_id', 'member_id', 'style_id', 'name', 'bottle_number', 'brewer_notes',
    ];

    public function competition(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function member(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function style(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Style::class);
    }

    public function scoresheets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Scoresheet::class);
    }
}
