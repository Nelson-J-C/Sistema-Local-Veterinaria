<?php

namespace App\Filament\Resources\VetTimeOffs;

use App\Enums\NavigationGroup;
use App\Filament\Resources\VetTimeOffs\Pages\CreateVetTimeOff;
use App\Filament\Resources\VetTimeOffs\Pages\EditVetTimeOff;
use App\Filament\Resources\VetTimeOffs\Pages\ListVetTimeOffs;
use App\Filament\Resources\VetTimeOffs\Schemas\VetTimeOffForm;
use App\Filament\Resources\VetTimeOffs\Tables\VetTimeOffsTable;
use App\Models\VetTimeOff;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class VetTimeOffResource extends Resource
{
    protected static ?string $model = VetTimeOff::class;

    protected static ?string $modelLabel = 'bloqueo de agenda';

    protected static ?string $pluralModelLabel = 'bloqueos de agenda';

    protected static ?string $navigationLabel = 'Bloqueos de agenda';

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Clinical;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return VetTimeOffForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VetTimeOffsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['vet', 'createdBy']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVetTimeOffs::route('/'),
            'create' => CreateVetTimeOff::route('/create'),
            'edit' => EditVetTimeOff::route('/{record}/edit'),
        ];
    }
}
