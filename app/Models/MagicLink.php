<?php

namespace App\Models;

use App\Models\Concerns\HasTokenExpiry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $member_id
 * @property string $token
 * @property Carbon $expires_at
 * @property Carbon|null $used_at
 */
class MagicLink extends Model
{
    use HasTokenExpiry;

    protected $fillable = ['member_id', 'token', 'expires_at', 'used_at'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public static function createForMember(Member $member): string
    {
        $plain = Str::random(64);

        self::create([
            'member_id' => $member->id,
            'token' => hash('sha256', $plain),
            'expires_at' => now()->addMinutes(15),
        ]);

        return $plain;
    }

    public static function findByPlaintext(string $plain): ?self
    {
        return self::where('token', hash('sha256', $plain))->first();
    }

    /**
     * Marks the link used in a single conditional UPDATE and returns whether this call won.
     * Of two concurrent requests with the same token, only one gets true.
     */
    public function consume(): bool
    {
        return self::whereKey($this->id)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['used_at' => now()]) === 1;
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
