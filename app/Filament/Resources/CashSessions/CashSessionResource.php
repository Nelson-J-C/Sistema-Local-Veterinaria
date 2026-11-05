<?php

namespace App\Filament\Resources\CashSessions;

use App\Enums\NavigationGroup;
use App\Filament\Resources\CashSessions\Pages\CreateCashSession;
use App\Filament\Resources\CashSessions\Pages\ListCashSessions;
use App\Filament\Resources\CashSessions\Pages\ViewCashSession;
use App\Filament\Resources\CashSessions\RelationManagers\MovementsRelationManager;
use App\Filament\Resources\CashSessions\Schemas\CashSessionForm;
use App\Filament\Resources\CashSessions\Schemas\CashSessionInfolist;
use App\Filament\Resources\CashSessions\Tables\CashSessionsTable;
use App\Models\CashSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CashSessionResource extends Resource
{
    protected static ?string $model = CashSession::class;

    protected static ?string $modelLabel = 'sesión de caja';

    protected static ?string $pluralModelLabel = 'sesiones de caja';

    protected static ?string $navigationLabel = 'Caja';

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Billing;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return CashSessionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CashSessionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashSessionsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['openedBy', 'closedBy'])
            ->withCount('movements');
    }

    public static function getRelations(): array
    {
        return [
            MovementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashSessions::route('/'),
            'create' => CreateCashSession::route('/create'),
            'view' => ViewCashSession::route('/{record}'),
        ];
    }
}
