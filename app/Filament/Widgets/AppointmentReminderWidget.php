<?php

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class AppointmentReminderWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('appointments.view') ?? false;
    }

    public function table(Table $table): Table
    {
        $days = max(0, (int) Setting::get('reminder_days_before', config('clinic.reminder_days_before'), 'appointments'));

        return $table
            ->heading('Recordatorios pendientes')
            ->description("Citas de los próximos {$days} días sin recordatorio registrado.")
            ->query(fn (): Builder => Appointment::query()
                ->with(['patient', 'owner', 'vet'])
                ->whereIn('status', [
                    AppointmentStatus::Requested->value,
                    AppointmentStatus::Scheduled->value,
                    AppointmentStatus::Confirmed->value,
                ])
                ->whereNull('reminder_sent_at')
                ->whereBetween('starts_at', [now(), now()->addDays($days)->endOfDay()])
                ->orderBy('starts_at'))
            ->columns([
                TextColumn::make('starts_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i'),
                TextColumn::make('patient.name')
                    ->label('Paciente'),
                TextColumn::make('owner.full_name')
                    ->label('Tutor')
                    ->getStateUsing(fn (Appointment $record): string => $record->owner->full_name),
                TextColumn::make('owner.phone')
                    ->label('Teléfono')
                    ->placeholder('-'),
                TextColumn::make('vet.name')
                    ->label('Veterinario'),
            ])
            ->recordActions([
                Action::make('mark_reminder')
                    ->label('Marcar enviado')
                    ->icon(Heroicon::OutlinedBellAlert)
                    ->color('info')
                    ->authorize('update')
                    ->requiresConfirmation()
                    ->action(function (Appointment $record): void {
                        $record->update(['reminder_sent_at' => now()]);

                        Notification::make()->success()->title('Recordatorio marcado como enviado')->send();
                    }),
            ])
            ->recordUrl(fn (Appointment $record): string => AppointmentResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading('Sin recordatorios pendientes')
            ->emptyStateDescription('Todas las citas próximas ya tienen recordatorio registrado.');
    }
}
