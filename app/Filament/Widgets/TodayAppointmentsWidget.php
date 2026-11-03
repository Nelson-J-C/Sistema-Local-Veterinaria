<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TodayAppointmentsWidget extends TableWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('appointments.view') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Citas de hoy')
            ->query(fn (): Builder => Appointment::query()
                ->with(['patient', 'owner', 'vet'])
                ->whereDate('starts_at', today())
                ->orderBy('starts_at'))
            ->columns([
                TextColumn::make('starts_at')
                    ->label('Hora')
                    ->dateTime('H:i'),
                TextColumn::make('patient.name')
                    ->label('Paciente')
                    ->searchable(),
                TextColumn::make('owner.full_name')
                    ->label('Tutor')
                    ->getStateUsing(fn (Appointment $record): string => $record->owner->full_name),
                TextColumn::make('vet.name')
                    ->label('Veterinario'),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
            ])
            ->recordUrl(fn (Appointment $record): string => AppointmentResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading('Sin citas para hoy')
            ->emptyStateDescription('La agenda del día está libre.');
    }
}
