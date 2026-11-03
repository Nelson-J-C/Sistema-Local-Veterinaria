<?php

namespace App\Filament\Resources\Appointments\Tables;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('starts_at')
                    ->label('Inicio')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Fin')
                    ->dateTime('H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('patient.name')
                    ->label('Paciente')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('owner.full_name')
                    ->label('Tutor')
                    ->getStateUsing(fn (Appointment $record): string => $record->owner->full_name)
                    ->searchable(['owner.first_name', 'owner.last_name'])
                    ->toggleable(),
                TextColumn::make('vet.name')
                    ->label('Veterinario')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('reason')
                    ->label('Motivo')
                    ->limit(40)
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('reminder_sent_at')
                    ->label('Recordatorio')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Pendiente')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('starts_at', 'desc')
            ->filters([
                Filter::make('date')
                    ->label('Rango de fechas')
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
                            ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('starts_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('starts_at', '<=', $date));
                    }),
                SelectFilter::make('vet_id')
                    ->label('Veterinario')
                    ->relationship('vet', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(AppointmentStatus::class),
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(AppointmentType::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ActionGroup::make([
                    self::confirmAction(),
                    self::checkInAction(),
                    self::startAction(),
                    self::completeAction(),
                    self::reminderAction(),
                    self::cancelAction(),
                    self::noShowAction(),
                ])
                    ->label('Cambiar estado')
                    ->icon(Heroicon::OutlinedArrowPath),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords('delete'),
                ]),
            ])
            ->emptyStateHeading('Sin citas registradas')
            ->emptyStateDescription('Cree la primera cita desde el botón "Nueva cita".');
    }

    private static function confirmAction(): Action
    {
        return Action::make('confirm')
            ->label('Confirmar')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->authorize('update')
            ->visible(fn (Appointment $record): bool => in_array($record->status, [AppointmentStatus::Requested, AppointmentStatus::Scheduled], true))
            ->requiresConfirmation()
            ->action(function (Appointment $record): void {
                $record->update(['status' => AppointmentStatus::Confirmed]);

                Notification::make()->success()->title('Cita confirmada')->send();
            });
    }

    private static function checkInAction(): Action
    {
        return Action::make('check_in')
            ->label('En sala de espera')
            ->icon(Heroicon::OutlinedClock)
            ->color('warning')
            ->authorize('update')
            ->visible(fn (Appointment $record): bool => in_array($record->status, [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed], true))
            ->requiresConfirmation()
            ->action(function (Appointment $record): void {
                $record->update(['status' => AppointmentStatus::CheckedIn]);

                Notification::make()->success()->title('Paciente en sala de espera')->send();
            });
    }

    private static function startAction(): Action
    {
        return Action::make('start')
            ->label('Iniciar atención')
            ->icon(Heroicon::OutlinedPlay)
            ->color('warning')
            ->authorize('update')
            ->visible(fn (Appointment $record): bool => $record->status === AppointmentStatus::CheckedIn)
            ->requiresConfirmation()
            ->action(function (Appointment $record): void {
                $record->update(['status' => AppointmentStatus::InProgress]);

                Notification::make()->success()->title('Atención iniciada')->send();
            });
    }

    private static function completeAction(): Action
    {
        return Action::make('complete')
            ->label('Completar')
            ->icon(Heroicon::OutlinedCheckBadge)
            ->color('success')
            ->authorize('update')
            ->visible(fn (Appointment $record): bool => in_array($record->status, [AppointmentStatus::CheckedIn, AppointmentStatus::InProgress], true))
            ->requiresConfirmation()
            ->action(function (Appointment $record): void {
                $record->update(['status' => AppointmentStatus::Completed]);

                Notification::make()->success()->title('Cita completada')->send();
            });
    }

    private static function reminderAction(): Action
    {
        return Action::make('mark_reminder')
            ->label('Marcar recordatorio enviado')
            ->icon(Heroicon::OutlinedBellAlert)
            ->color('info')
            ->authorize('update')
            ->visible(fn (Appointment $record): bool => $record->reminder_sent_at === null
                && $record->status->isActive()
                && $record->starts_at->isFuture())
            ->requiresConfirmation()
            ->action(function (Appointment $record): void {
                $record->update(['reminder_sent_at' => now()]);

                Notification::make()->success()->title('Recordatorio marcado como enviado')->send();
            });
    }

    private static function cancelAction(): Action
    {
        return Action::make('cancel')
            ->label('Cancelar')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->authorize('update')
            ->visible(fn (Appointment $record): bool => $record->status->isActive())
            ->schema([
                Textarea::make('cancellation_reason')
                    ->label('Motivo de cancelación')
                    ->required()
                    ->maxLength(255)
                    ->rows(2),
            ])
            ->action(function (Appointment $record, array $data): void {
                $record->update([
                    'status' => AppointmentStatus::Cancelled,
                    'cancelled_at' => now(),
                    'cancelled_by' => auth()->id(),
                    'cancellation_reason' => $data['cancellation_reason'],
                ]);

                Notification::make()->success()->title('Cita cancelada')->send();
            });
    }

    private static function noShowAction(): Action
    {
        return Action::make('no_show')
            ->label('No asistió')
            ->icon(Heroicon::OutlinedUserMinus)
            ->color('danger')
            ->authorize('update')
            ->visible(fn (Appointment $record): bool => $record->status->isActive()
                && $record->starts_at->isPast())
            ->requiresConfirmation()
            ->action(function (Appointment $record): void {
                $record->update(['status' => AppointmentStatus::NoShow]);

                Notification::make()->success()->title('Cita marcada como no asistida')->send();
            });
    }

    /**
     * Build a link to the appointment edit page, used by the agenda view.
     */
    public static function editUrl(Appointment $appointment): string
    {
        return AppointmentResource::getUrl('edit', ['record' => $appointment]);
    }

    /**
     * Format a time range for the agenda cards.
     */
    public static function timeRange(Appointment $appointment): string
    {
        return $appointment->starts_at->format('H:i').'–'.$appointment->ends_at->format('H:i');
    }
}
