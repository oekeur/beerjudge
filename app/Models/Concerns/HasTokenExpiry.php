<?php

namespace App\Models\Concerns;

use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $expires_at
 * @property Carbon|null $used_at
 */
trait HasTokenExpiry
{
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
}
