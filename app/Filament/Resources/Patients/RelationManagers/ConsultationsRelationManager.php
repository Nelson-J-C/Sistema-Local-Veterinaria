<?php

namespace App\Filament\Resources\Patients\RelationManagers;

use App\Filament\Resources\Consultations\ConsultationResource;
use App\Models\Consultation;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConsultationsRelationManager extends RelationManager
{
    protected static string $relationship = 'consultations';

    protected static ?string $title = 'Historial de consultas';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('chief_complaint')
            ->columns([
                TextColumn::make('consulted_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('vet.name')
                    ->label('Veterinario'),
                TextColumn::make('chief_complaint')
                    ->label('Motivo')
                    ->limit(50)
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('next_visit_at')
                    ->label('Próximo control')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
            ])
            ->defaultSort('consulted_at', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Consultation $record): string => ConsultationResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading('Sin consultas registradas')
            ->emptyStateDescription('Cree la primera consulta desde la ficha del paciente.');
    }
}
