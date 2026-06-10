<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Scoresheet extends Model
{
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

    public function entry(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    public function judge(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Member::class, 'judge_id');
    }
}
