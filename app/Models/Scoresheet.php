<?php

namespace App\Models;

use App\Enums\ScoresheetStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int|null $client_revision
 */
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
        'overall_notes', 'descriptors', 'client_uuid', 'client_revision', 'status', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'descriptors' => 'array',
            'submitted_at' => 'datetime',
            'client_revision' => 'integer',
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
     * The sheet as the judging screen holds it in the browser.
     *
     * @return array<string, mixed>
     */
    public function toJudgingPayload(): array
    {
        return [
            'entry_id' => $this->entry_id,
            'client_uuid' => $this->client_uuid,
            'client_revision' => $this->client_revision,
            'aroma' => $this->aroma,
            'appearance' => $this->appearance,
            'flavour' => $this->flavour,
            'mouthfeel' => $this->mouthfeel,
            'overall' => $this->overall,
            'aroma_notes' => $this->aroma_notes ?? '',
            'appearance_notes' => $this->appearance_notes ?? '',
            'flavour_notes' => $this->flavour_notes ?? '',
            'mouthfeel_notes' => $this->mouthfeel_notes ?? '',
            'overall_notes' => $this->overall_notes ?? '',
            'descriptors' => $this->descriptors ?? [],
            'status' => $this->status,
        ];
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
