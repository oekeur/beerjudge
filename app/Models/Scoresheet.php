<?php

namespace App\Models;

use App\Enums\ScoresheetStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Scoresheet extends Model
{
    /** @var array<string, int> */
    public const SECTION_MAX = [
        'aroma' => 12,
        'appearance' => 3,
        'flavour' => 20,
        'mouthfeel' => 5,
        'overall' => 10,
    ];

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
            'status' => ScoresheetStatus::class,
        ];
    }

    protected static function booting(): void
    {
        static::saving(function (Scoresheet $sheet) {
            $sheet->total = $sheet->aroma + $sheet->appearance
                + $sheet->flavour + $sheet->mouthfeel + $sheet->overall;
        });
    }

    /**
     * @return BelongsTo<Entry, $this>
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function judge(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'judge_id');
    }
}
