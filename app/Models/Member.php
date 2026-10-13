<?php

namespace App\Models;

use App\Notifications\MemberActivatedNotification;
use Database\Factories\MemberFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property bool $is_admin
 * @property bool $is_active
 * @property string $locale
 */
class Member extends Authenticatable implements CanResetPasswordContract, FilamentUser
{
    /** @use HasFactory<MemberFactory> */
    use CanResetPassword, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'is_admin', 'is_active', 'locale',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Lowercases and trims an address. Emails are stored this way, so every lookup
     * normalises its input the same way before comparing.
     */
    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public static function findByEmail(string $email): ?self
    {
        return static::query()->where('email', self::normalizeEmail($email))->first();
    }

    /**
     * @return Attribute<string, string>
     */
    protected function email(): Attribute
    {
        return Attribute::make(set: fn (string $value): string => self::normalizeEmail($value));
    }

    /**
     * Emails the member when an admin activates the account, whichever Filament path did it.
     */
    protected static function booted(): void
    {
        static::updated(function (Member $member): void {
            if ($member->wasChanged('is_active') && $member->is_active) {
                $member->notify((new MemberActivatedNotification)->locale($member->locale));
            }
        });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    /**
     * True when this member is an active admin and no other active admin exists.
     */
    public function isLastActiveAdmin(): bool
    {
        return $this->is_admin
            && $this->is_active
            && ! static::query()->where('is_admin', true)->where('is_active', true)->whereKeyNot($this->getKey())->exists();
    }

    /**
     * @return HasMany<Entry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    /**
     * @return HasMany<Scoresheet, $this>
     */
    public function scoresheets(): HasMany
    {
        return $this->hasMany(Scoresheet::class, 'judge_id');
    }
}
