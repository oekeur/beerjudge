<?php

namespace App\Support;

class PasswordRules
{
    /** @return array<int, string> */
    public static function required(): array
    {
        return ['required', 'string', 'min:8', 'confirmed'];
    }

    /** @return array<int, string> */
    public static function optional(): array
    {
        return ['nullable', 'string', 'min:8', 'confirmed'];
    }
}
