<?php

namespace App\Auth;

use App\Models\Member;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Lowercases the email before the credential lookup. Covers every path that goes
 * through the guard or the password broker: the member login, the Filament login,
 * sending a reset link and resetting a password.
 */
class MemberUserProvider extends EloquentUserProvider
{
    /**
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(#[\SensitiveParameter] array $credentials): ?Authenticatable
    {
        if (is_string($credentials['email'] ?? null)) {
            $credentials['email'] = Member::normalizeEmail($credentials['email']);
        }

        return parent::retrieveByCredentials($credentials);
    }
}
