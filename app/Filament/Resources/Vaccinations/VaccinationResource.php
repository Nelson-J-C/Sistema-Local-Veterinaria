<?php

namespace App\Filament\Resources\Vaccinations;

use App\Enums\NavigationGroup;
use App\Filament\Resources\Vaccinations\Pages\CreateVaccination;
use App\Filament\Resources\Vaccinations\Pages\EditVaccination;
use App\Filament\Resources\Vaccinations\Pages\ListVaccinations;
use App\Filament\Resources\Vaccinations\Schemas\VaccinationForm;
use App\Filament\Resources\Vaccinations\Tables\VaccinationsTable;
use App\Models\Vaccination;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class VaccinationResource extends Resource
{
    protected static ?string $model = Vaccination::class;

    protected static ?string $modelLabel = 'vacunación';

    protected static ?string $pluralModelLabel = 'vacunaciones';

    protected static ?string $navigationLabel = 'Vacunas aplicadas';

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Clinical;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return VaccinationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VaccinationsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['patient.owner', 'vaccine', 'vet', 'consultation']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVaccinations::route('/'),
            'create' => CreateVaccination::route('/create'),
            'edit' => EditVaccination::route('/{record}/edit'),
        ];
    }
}
