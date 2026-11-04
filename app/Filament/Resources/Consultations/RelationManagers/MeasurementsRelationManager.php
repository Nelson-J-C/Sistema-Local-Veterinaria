<?php

namespace App\Filament\Resources\Consultations\RelationManagers;

use App\Models\Consultation;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MeasurementsRelationManager extends RelationManager
{
    protected static string $relationship = 'measurements';

    protected static ?string $title = 'Signos vitales y medidas';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Medición')
                    ->columns(3)
                    ->schema([
                        DateTimePicker::make('measured_at')
                            ->label('Fecha de medición')
                            ->required()
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->default(now()),
                        TextInput::make('weight_kg')
                            ->label('Peso')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(999)
                            ->step(0.01)
                            ->suffix('kg'),
                        TextInput::make('temperature_c')
                            ->label('Temperatura')
                            ->numeric()
                            ->minValue(20)
                            ->maxValue(50)
                            ->step(0.1)
                            ->suffix('°C'),
                        TextInput::make('heart_rate')
                            ->label('Frecuencia cardíaca')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(400)
                            ->suffix('lpm'),
                        TextInput::make('respiratory_rate')
                            ->label('Frecuencia respiratoria')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(200)
                            ->suffix('rpm'),
                        TextInput::make('body_condition_score')
                            ->label('Condición corporal')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(9)
                            ->helperText('Escala de 1 a 9.'),
                        TextInput::make('pain_score')
                            ->label('Dolor')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(4)
                            ->helperText('Escala de 0 a 4.'),
                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('measured_at')
            ->columns([
                TextColumn::make('measured_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('weight_kg')
                    ->label('Peso')
                    ->suffix(' kg')
                    ->placeholder('-'),
                TextColumn::make('temperature_c')
                    ->label('Temperatura')
                    ->suffix(' °C')
                    ->placeholder('-'),
                TextColumn::make('heart_rate')
                    ->label('FC')
                    ->suffix(' lpm')
                    ->placeholder('-'),
                TextColumn::make('respiratory_rate')
                    ->label('FR')
                    ->suffix(' rpm')
                    ->placeholder('-'),
                TextColumn::make('body_condition_score')
                    ->label('CC')
                    ->placeholder('-'),
                TextColumn::make('pain_score')
                    ->label('Dolor')
                    ->placeholder('-'),
                TextColumn::make('recordedBy.name')
                    ->label('Registrada por')
                    ->placeholder('Sistema')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('notes')
                    ->label('Notas')
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('measured_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Registrar medición')
                    ->mutateDataUsing(fn (array $data): array => [
                        ...$data,
                        'patient_id' => $this->selectedConsultation()->patient_id,
                        'recorded_by' => auth()->id(),
                    ]),
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
            ->emptyStateHeading('Sin mediciones registradas')
            ->emptyStateDescription('Registre peso, temperatura y signos vitales del paciente.');
    }

    /**
     * Resolve the consultation that owns this relation manager.
     */
    private function selectedConsultation(): Consultation
    {
        /** @var Consultation $consultation */
        $consultation = $this->getOwnerRecord();

        return $consultation;
    }
}
