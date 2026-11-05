<?php

namespace App\Filament\Resources\Dewormings\Schemas;

use App\Models\Consultation;
use App\Models\Deworming;
use App\Models\Patient;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class DewormingForm
{
    public static function configure(Schema $schema, bool $includePatient = true, bool $includeConsultation = true): Schema
    {
        return $schema
            ->components([
                Section::make('Paciente y aplicación')
                    ->columns(2)
                    ->schema([
                        Select::make('patient_id')
                            ->label('Paciente')
                            ->relationship(
                                name: 'patient',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->with('owner'),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Patient $record): string => "{$record->name} · {$record->owner->full_name}")
                            ->searchable(['name'])
                            ->preload()
                            ->required($includePatient)
                            ->visible($includePatient)
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                $weight = Patient::query()->whereKey($state)->value('weight_kg');

                                if (filled($weight)) {
                                    $set('weight_kg', $weight);
                                }
                            }),
                        Select::make('consultation_id')
                            ->label('Consulta relacionada')
                            ->options(function (Get $get, ?Deworming $record): array {
                                $patientId = $get('patient_id') ?? $record?->patient_id;

                                return Consultation::query()
                                    ->when(
                                        filled($patientId),
                                        fn (Builder $query): Builder => $query->where('patient_id', $patientId),
                                    )
                                    ->orderByDesc('consulted_at')
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn (Consultation $consultation): array => [
                                        $consultation->getKey() => $consultation->consulted_at->format('d/m/Y H:i')
                                            .' · '.$consultation->chief_complaint,
                                    ])
                                    ->all();
                            })
                            ->searchable()
                            ->placeholder('Sin consulta asociada')
                            ->helperText('Opcional. Permite vincular la aplicación con la atención clínica.')
                            ->visible($includeConsultation),
                        TextInput::make('product_name')
                            ->label('Producto')
                            ->required()
                            ->maxLength(150)
                            ->placeholder('Ej. Albendazol'),
                        Select::make('product_id')
                            ->label('Producto del inventario')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Sin vincular')
                            ->helperText('Opcional. Vincula la aplicación con el catálogo de productos.'),
                        TextInput::make('dose')
                            ->label('Dosis aplicada')
                            ->maxLength(100)
                            ->placeholder('Ej. 1 tableta'),
                        DateTimePicker::make('administered_at')
                            ->label('Fecha de aplicación')
                            ->required()
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->default(now()),
                        DateTimePicker::make('next_due_at')
                            ->label('Próxima dosis')
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->helperText('Se usa para generar el recordatorio interno.'),
                        TextInput::make('weight_kg')
                            ->label('Peso al momento')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(999)
                            ->step(0.01)
                            ->suffix('kg'),
                        Select::make('vet_id')
                            ->label('Veterinario')
                            ->options(fn (): array => User::query()
                                ->where('active', true)
                                ->role(['veterinario', 'admin'])
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->default(fn (): ?int => auth()->user()?->hasRole('veterinario') ? auth()->id() : null)
                            ->searchable(),
                    ]),
                Section::make('Notas')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3),
                    ]),
            ]);
    }
}
