<?php

namespace App\Filament\Widgets;

use App\Enums\ReminderStatus;
use App\Enums\ReminderType;
use App\Filament\Resources\Patients\PatientResource;
use App\Models\Reminder;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PreventiveReminderWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('reminders.view') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Vacunas y desparasitaciones próximas')
            ->description('Recordatorios pendientes de los próximos 30 días o ya vencidos.')
            ->query(fn (): Builder => Reminder::query()
                ->with(['patient', 'owner'])
                ->where('status', ReminderStatus::Pending)
                ->whereIn('type', [ReminderType::Vaccination, ReminderType::Deworming])
                ->whereDate('due_at', '<=', now()->addDays(30)->endOfDay())
                ->orderBy('due_at'))
            ->columns([
                TextColumn::make('due_at')
                    ->label('Vence')
                    ->dateTime('d/m/Y')
                    ->color(fn (Reminder $record): string => $record->isOverdue() ? 'danger' : 'gray'),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('notes')
                    ->label('Detalle')
                    ->wrap(),
                TextColumn::make('patient.name')
                    ->label('Paciente'),
                TextColumn::make('owner.full_name')
                    ->label('Tutor')
                    ->getStateUsing(fn (Reminder $record): string => filled($record->owner_id) ? $record->owner->full_name : '-'),
            ])
            ->recordActions([
                Action::make('mark_sent')
                    ->label('Marcar enviado')
                    ->icon(Heroicon::OutlinedBellAlert)
                    ->color('info')
                    ->authorize('update')
                    ->requiresConfirmation()
                    ->successNotificationTitle('Recordatorio marcado como enviado')
                    ->action(fn (Reminder $record) => $record->markAsSent()),
            ])
            ->recordUrl(fn (Reminder $record): ?string => $record->patient_id !== null
                ? PatientResource::getUrl('view', ['record' => $record->patient_id])
                : null)
            ->emptyStateHeading('Sin recordatorios próximos')
            ->emptyStateDescription('No hay vacunas ni desparasitaciones pendientes en los próximos 30 días.');
    }
}
