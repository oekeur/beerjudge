<?php

namespace App\Filament\Resources\Members\Schemas;

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
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->password()
                    ->visibleOn('create')
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                    ->dehydrated(fn ($state) => filled($state)),
                Select::make('locale')
                    ->options(['en' => 'English', 'nl' => 'Nederlands'])
                    ->default('en')
                    ->required(),
                Toggle::make('is_admin'),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
