<?php

namespace App\Models\Concerns;

use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $expires_at
 * @property Carbon|null $used_at
 */
trait HasTokenExpiry
{
    public const REASON_USED = 'used';

    public const REASON_EXPIRED = 'expired';

    public function isValid(): bool
    {
        return $this->invalidReason() === null;
    }

    /**
     * Why the token cannot be used, or null when it still can be.
     */
    public function invalidReason(): ?string
    {
        if ($this->used_at !== null) {
            return self::REASON_USED;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return self::REASON_EXPIRED;
        }

        return null;
    }

    public function markUsed(): void
    {
        $this->update(['used_at' => now()]);
    }
}
