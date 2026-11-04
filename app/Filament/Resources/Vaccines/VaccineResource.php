<?php

namespace App\Filament\Resources\Vaccines;

use App\Enums\NavigationGroup;
use App\Filament\Resources\Vaccines\Pages\CreateVaccine;
use App\Filament\Resources\Vaccines\Pages\EditVaccine;
use App\Filament\Resources\Vaccines\Pages\ListVaccines;
use App\Filament\Resources\Vaccines\Schemas\VaccineForm;
use App\Filament\Resources\Vaccines\Tables\VaccinesTable;
use App\Models\Vaccine;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class VaccineResource extends Resource
{
    protected static ?string $model = Vaccine::class;

    protected static ?string $modelLabel = 'vacuna';

    protected static ?string $pluralModelLabel = 'catálogo de vacunas';

    protected static ?string $navigationLabel = 'Vacunas';

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Configuration;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return VaccineForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VaccinesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('species')
            ->withCount('vaccinations');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVaccines::route('/'),
            'create' => CreateVaccine::route('/create'),
            'edit' => EditVaccine::route('/{record}/edit'),
        ];
    }
}
