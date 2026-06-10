<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Competition extends Model
{
    const STATUS_DRAFT = 'draft';
    const STATUS_OPEN = 'open';
    const STATUS_JUDGING = 'judging';
    const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'name', 'year', 'judging_date', 'status', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'judging_date' => 'date',
            'published_at' => 'datetime',
        ];
    }

    public function styles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Style::class, 'competition_style');
    }

    public function entries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Entry::class);
    }
}
