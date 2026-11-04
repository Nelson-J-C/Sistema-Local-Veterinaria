<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Enums\AppointmentStatus;
use App\Filament\Resources\Consultations\ConsultationResource;
use App\Models\Appointment;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AppointmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Cita')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('patient.name')
                            ->label('Paciente'),
                        TextEntry::make('owner.full_name')
                            ->label('Tutor'),
                        TextEntry::make('vet.name')
                            ->label('Veterinario'),
                        TextEntry::make('type')
                            ->label('Tipo')
                            ->badge(),
                        TextEntry::make('starts_at')
                            ->label('Inicio')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('ends_at')
                            ->label('Fin')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('duration_minutes')
                            ->label('Duración')
                            ->suffix(' min'),
                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge(),
                        TextEntry::make('reason')
                            ->label('Motivo')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('notes')
                            ->label('Notas internas')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('consultation.consulted_at')
                            ->label('Consulta clínica')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Sin consulta registrada')
                            ->url(fn (Appointment $record): ?string => $record->consultation
                                ? ConsultationResource::getUrl('view', ['record' => $record->consultation])
                                : null)
                            ->columnSpanFull(),
                    ]),
                Section::make('Cancelación')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('cancelled_at')
                            ->label('Cancelada el')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('cancelledBy.name')
                            ->label('Cancelada por')
                            ->placeholder('-'),
                        TextEntry::make('cancellation_reason')
                            ->label('Motivo de cancelación')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Appointment $record): bool => $record->status === AppointmentStatus::Cancelled),
                Section::make('Registro')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('createdBy.name')
                            ->label('Registrada por')
                            ->placeholder('Sistema'),
                        TextEntry::make('reminder_sent_at')
                            ->label('Recordatorio enviado')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Pendiente'),
                        TextEntry::make('created_at')
                            ->label('Creada')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('Actualizada')
                            ->dateTime(),
                    ]),
            ]);
    }
}
