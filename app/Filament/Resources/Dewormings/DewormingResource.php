<?php

namespace App\Filament\Resources\Dewormings;

use App\Enums\NavigationGroup;
use App\Filament\Resources\Dewormings\Pages\CreateDeworming;
use App\Filament\Resources\Dewormings\Pages\EditDeworming;
use App\Filament\Resources\Dewormings\Pages\ListDewormings;
use App\Filament\Resources\Dewormings\Schemas\DewormingForm;
use App\Filament\Resources\Dewormings\Tables\DewormingsTable;
use App\Models\Deworming;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DewormingResource extends Resource
{
    protected static ?string $model = Deworming::class;

    protected static ?string $modelLabel = 'desparasitación';

    protected static ?string $pluralModelLabel = 'desparasitaciones';

    protected static ?string $navigationLabel = 'Desparasitaciones';

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Clinical;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBugAnt;

    protected static ?int $navigationSort = 60;

    public static function form(Schema $schema): Schema
    {
        return DewormingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DewormingsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['patient.owner', 'vet', 'consultation']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDewormings::route('/'),
            'create' => CreateDeworming::route('/create'),
            'edit' => EditDeworming::route('/{record}/edit'),
        ];
    }
}
