<?php

namespace App\Filament\Resources\Consultations\RelationManagers;

use App\Enums\MedicationRoute;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PrescriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'prescriptions';

    protected static ?string $title = 'Recetas';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Medicamento')
                    ->columns(2)
                    ->schema([
                        TextInput::make('medication_name')
                            ->label('Medicamento')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('presentation')
                            ->label('Presentación')
                            ->maxLength(100)
                            ->placeholder('Ej. Jarabe 120 ml'),
                        TextInput::make('dose')
                            ->label('Dosis')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Ej. 1 ml'),
                        TextInput::make('frequency')
                            ->label('Frecuencia')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Ej. Cada 12 horas'),
                        Select::make('route')
                            ->label('Vía')
                            ->options(MedicationRoute::class)
                            ->default(MedicationRoute::Oral)
                            ->required(),
                        TextInput::make('duration_days')
                            ->label('Duración')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(365)
                            ->suffix('días'),
                        TextInput::make('quantity')
                            ->label('Cantidad')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01),
                        Textarea::make('instructions')
                            ->label('Indicaciones')
                            ->rows(3)
                            ->columnSpanFull(),
                        Toggle::make('dispensed')
                            ->label('Dispensado'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('medication_name')
            ->columns([
                TextColumn::make('medication_name')
                    ->label('Medicamento')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('presentation')
                    ->label('Presentación')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('dose')
                    ->label('Dosis'),
                TextColumn::make('frequency')
                    ->label('Frecuencia'),
                TextColumn::make('route')
                    ->label('Vía')
                    ->badge(),
                TextColumn::make('duration_days')
                    ->label('Duración')
                    ->suffix(' días')
                    ->placeholder('-'),
                TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->placeholder('-'),
                IconColumn::make('dispensed')
                    ->label('Dispensado')
                    ->boolean(),
                TextColumn::make('instructions')
                    ->label('Indicaciones')
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('medication_name')
            ->filters([
                SelectFilter::make('route')
                    ->label('Vía')
                    ->options(MedicationRoute::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Agregar medicamento'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Sin medicamentos recetados')
            ->emptyStateDescription('Registre los medicamentos indicados al paciente.');
    }
}
