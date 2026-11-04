<?php

namespace App\Filament\Resources\Reminders;

use App\Enums\NavigationGroup;
use App\Filament\Resources\Reminders\Pages\ListReminders;
use App\Filament\Resources\Reminders\Tables\RemindersTable;
use App\Models\Reminder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ReminderResource extends Resource
{
    protected static ?string $model = Reminder::class;

    protected static ?string $modelLabel = 'recordatorio';

    protected static ?string $pluralModelLabel = 'recordatorios';

    protected static ?string $navigationLabel = 'Recordatorios';

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Clinical;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static ?int $navigationSort = 70;

    public static function table(Table $table): Table
    {
        return RemindersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['patient', 'owner']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReminders::route('/'),
        ];
    }
}
