<?php

namespace App\Filament\Resources\Owners\Tables;

use App\Enums\DocumentType;
use App\Models\Owner;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class OwnersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Tutor')
                    ->getStateUsing(fn (Owner $record): string => $record->full_name)
                    ->searchable(['first_name', 'last_name', 'business_name'])
                    ->sortable(['first_name']),
                TextColumn::make('document_type')
                    ->label('Documento')
                    ->badge()
                    ->formatStateUsing(fn (DocumentType $state): string => $state->getLabel())
                    ->toggleable(),
                TextColumn::make('document_number')
                    ->label('Número')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('email')
                    ->label('Correo electrónico')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('city')
                    ->label('Ciudad')
                    ->searchable()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('patients_count')
                    ->label('Pacientes')
                    ->counts('patients')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                IconColumn::make('active')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime()
                    ->sortable()
                    ->visible(fn (?Owner $record): bool => $record?->trashed() ?? false)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('first_name')
            ->filters([
                SelectFilter::make('document_type')
                    ->label('Tipo de documento')
                    ->options(DocumentType::class),
                SelectFilter::make('active')
                    ->label('Estado')
                    ->options([
                        1 => 'Activos',
                        0 => 'Inactivos',
                    ]),
                TrashedFilter::make()
                    ->label('Eliminados'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords('delete'),
                    RestoreBulkAction::make()
                        ->authorizeIndividualRecords('restore'),
                    ForceDeleteBulkAction::make()
                        ->authorizeIndividualRecords('forceDelete'),
                ]),
            ])
            ->emptyStateHeading('Sin tutores registrados')
            ->emptyStateDescription('Registre al primer tutor para comenzar a crear pacientes.');
    }
}
