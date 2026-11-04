<?php

namespace App\Filament\Resources\Consultations\Schemas;

use App\Models\Consultation;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ConsultationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Consulta')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('patient.name')
                            ->label('Paciente'),
                        TextEntry::make('owner_name')
                            ->label('Tutor')
                            ->getStateUsing(fn (Consultation $record): string => $record->patient->owner->full_name),
                        TextEntry::make('vet.name')
                            ->label('Veterinario'),
                        TextEntry::make('consulted_at')
                            ->label('Fecha de consulta')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge(),
                        TextEntry::make('appointment.starts_at')
                            ->label('Cita vinculada')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Sin cita'),
                        TextEntry::make('chief_complaint')
                            ->label('Motivo de consulta')
                            ->columnSpanFull(),
                    ]),
                Section::make('Registro SOAP')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('subjective')
                            ->label('Subjetivo')
                            ->placeholder('-'),
                        TextEntry::make('objective')
                            ->label('Objetivo')
                            ->placeholder('-'),
                        TextEntry::make('assessment')
                            ->label('Evaluación')
                            ->placeholder('-'),
                        TextEntry::make('plan')
                            ->label('Plan')
                            ->placeholder('-'),
                    ]),
                Section::make('Cierre y seguimiento')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('next_visit_at')
                            ->label('Próximo control')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('finalized_at')
                            ->label('Finalizada el')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('finalizedBy.name')
                            ->label('Finalizada por')
                            ->placeholder('-'),
                        TextEntry::make('createdBy.name')
                            ->label('Registrada por')
                            ->placeholder('Sistema'),
                        TextEntry::make('created_at')
                            ->label('Creada')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('updated_at')
                            ->label('Actualizada')
                            ->dateTime('d/m/Y H:i'),
                    ]),
            ]);
    }
}
