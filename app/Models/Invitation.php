<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $token
 * @property string $type
 * @property Carbon|null $expires_at
 * @property Carbon|null $used_at
 * @property int $created_by
 */
class Invitation extends Model
{
    const TYPE_SINGLE_USE = 'single_use';

    const TYPE_TIME_BOUND = 'time_bound';

    protected $fillable = ['token', 'type', 'expires_at', 'used_at', 'created_by'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public static function generate(string $type, ?Carbon $expiresAt, int $createdBy): self
    {
        return self::create([
            'token' => Str::random(64),
            'type' => $type,
            'expires_at' => $expiresAt,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeValid(Builder $query): void
    {
        $query->whereNull('used_at')
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function isValid(): bool
    {
        if ($this->used_at !== null) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function markUsed(): void
    {
        $this->update(['used_at' => now()]);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'created_by');
    }
}
