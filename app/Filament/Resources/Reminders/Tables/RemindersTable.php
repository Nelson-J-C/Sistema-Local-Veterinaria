<?php

namespace App\Filament\Resources\Reminders\Tables;

use App\Enums\ReminderStatus;
use App\Enums\ReminderType;
use App\Filament\Resources\Patients\PatientResource;
use App\Models\Reminder;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RemindersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('due_at')
                    ->label('Vencimiento')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->color(fn (Reminder $record): string => $record->isOverdue() ? 'danger' : 'gray'),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('notes')
                    ->label('Detalle')
                    ->placeholder('-')
                    ->wrap(),
                TextColumn::make('patient.name')
                    ->label('Paciente')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('owner.full_name')
                    ->label('Tutor')
                    ->getStateUsing(fn (Reminder $record): string => filled($record->owner_id) ? $record->owner->full_name : '-')
                    ->searchable(['owner.first_name', 'owner.last_name']),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('channel')
                    ->label('Canal')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('sent_at')
                    ->label('Enviado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('due_at')
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(ReminderStatus::class)
                    ->default(ReminderStatus::Pending->value),
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(ReminderType::class),
                Filter::make('due')
                    ->label('Vencimiento')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Desde')
                            ->displayFormat('d/m/Y'),
                        DatePicker::make('until')
                            ->label('Hasta')
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('due_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('due_at', '<=', $date));
                    }),
            ])
            ->recordActions([
                Action::make('mark_sent')
                    ->label('Marcar enviado')
                    ->icon(Heroicon::OutlinedBellAlert)
                    ->color('info')
                    ->authorize('update')
                    ->visible(fn (Reminder $record): bool => $record->status === ReminderStatus::Pending)
                    ->requiresConfirmation()
                    ->successNotificationTitle('Recordatorio marcado como enviado')
                    ->action(fn (Reminder $record) => $record->markAsSent()),
                Action::make('dismiss')
                    ->label('Descartar')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('gray')
                    ->authorize('update')
                    ->visible(fn (Reminder $record): bool => $record->status === ReminderStatus::Pending)
                    ->requiresConfirmation()
                    ->successNotificationTitle('Recordatorio descartado')
                    ->action(fn (Reminder $record) => $record->dismiss()),
            ])
            ->recordUrl(fn (Reminder $record): ?string => $record->patient_id !== null
                ? PatientResource::getUrl('view', ['record' => $record->patient_id])
                : null)
            ->emptyStateHeading('Sin recordatorios')
            ->emptyStateDescription('Los recordatorios de vacunas y desparasitaciones se generan al registrar la próxima dosis.');
    }
}
