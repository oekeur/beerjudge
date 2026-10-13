<?php

namespace App\Filament\Resources\Members\Schemas;

use App\Models\Member;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->rules([fn (?Member $record): Closure => self::uniqueIgnoringCase($record)]),
                TextInput::make('password')
                    ->password()
                    ->visibleOn('create')
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? bcrypt($state) : null)
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                Select::make('locale')
                    ->options(['en' => 'English', 'nl' => 'Nederlands'])
                    ->default('en')
                    ->required(),
                Toggle::make('is_admin')
                    ->rules([fn (?Member $record): Closure => self::keepLastActiveAdmin($record, 'members.last_admin_demote')]),
                Toggle::make('is_active')
                    ->default(true)
                    ->rules([fn (?Member $record): Closure => self::keepLastActiveAdmin($record, 'members.last_admin_deactivate')]),
            ]);
    }

    /**
     * Member stores emails lowercased, so the plain unique rule misses "Jan@x" against "jan@x"
     * and the save would hit the database's unique index instead.
     */
    private static function uniqueIgnoringCase(?Member $record): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($record): void {
            $taken = is_string($value) && Member::query()
                ->where('email', Member::normalizeEmail($value))
                ->when($record !== null, fn ($query) => $query->whereKeyNot($record?->getKey()))
                ->exists();

            if ($taken) {
                $fail(__('validation.unique'));
            }
        };
    }

    /**
     * Fails validation when the toggle is switched off on the only active admin.
     */
    private static function keepLastActiveAdmin(?Member $record, string $messageKey): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($record, $messageKey): void {
            if (! $value && $record?->isLastActiveAdmin()) {
                $fail(__($messageKey));
            }
        };
    }
}
