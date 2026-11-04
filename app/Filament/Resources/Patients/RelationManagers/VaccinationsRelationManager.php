<?php

namespace App\Filament\Resources\Patients\RelationManagers;

use App\Filament\Resources\Vaccinations\Schemas\VaccinationForm;
use App\Filament\Resources\Vaccinations\Tables\VaccinationsTable;
use App\Models\Patient;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class VaccinationsRelationManager extends RelationManager
{
    protected static string $relationship = 'vaccinations';

    protected static ?string $title = 'Vacunas';

    /**
     * Vaccinations can be recorded from the patient chart.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return VaccinationForm::configure(
            $schema,
            includePatient: false,
            includeConsultation: false,
        );
    }

    public function table(Table $table): Table
    {
        return VaccinationsTable::configure($table, showPatient: false)
            ->headerActions([
                CreateAction::make()
                    ->label('Registrar vacuna')
                    ->mutateDataUsing(fn (array $data): array => [
                        ...$data,
                        'patient_id' => $this->selectedPatient()->getKey(),
                        'created_by' => auth()->id(),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    /**
     * Resolve the patient that owns this relation manager.
     */
    private function selectedPatient(): Patient
    {
        /** @var Patient $patient */
        $patient = $this->getOwnerRecord();

        return $patient;
    }
}
