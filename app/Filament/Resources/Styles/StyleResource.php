<?php

namespace App\Filament\Resources\Styles;

use App\Filament\Resources\Styles\Pages\EditStyle;
use App\Filament\Resources\Styles\Pages\ListStyles;
use App\Filament\Resources\Styles\Schemas\StyleForm;
use App\Filament\Resources\Styles\Tables\StylesTable;
use App\Models\Style;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class StyleResource extends Resource
{
    protected static ?string $model = Style::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return StyleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StylesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return true;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStyles::route('/'),
            'edit' => EditStyle::route('/{record}/edit'),
        ];
    }
}
