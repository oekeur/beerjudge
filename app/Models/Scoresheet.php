<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Scoresheet extends Model
{
    protected $attributes = ['status' => 'draft'];

    protected $fillable = [
        'entry_id', 'judge_id', 'aroma', 'appearance', 'flavour', 'mouthfeel', 'overall',
        'total', 'aroma_notes', 'appearance_notes', 'flavour_notes', 'mouthfeel_notes',
        'overall_notes', 'client_uuid', 'status', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    protected static function booting(): void
    {
        static::saving(function (Scoresheet $sheet) {
            $sheet->total = $sheet->aroma + $sheet->appearance
                + $sheet->flavour + $sheet->mouthfeel + $sheet->overall;
        });
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'judge_id');
    }
}
