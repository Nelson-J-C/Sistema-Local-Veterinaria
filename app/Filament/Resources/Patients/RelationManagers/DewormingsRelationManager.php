<?php

namespace App\Filament\Resources\Patients\RelationManagers;

use App\Filament\Resources\Dewormings\Schemas\DewormingForm;
use App\Filament\Resources\Dewormings\Tables\DewormingsTable;
use App\Models\Patient;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class DewormingsRelationManager extends RelationManager
{
    protected static string $relationship = 'dewormings';

    protected static ?string $title = 'Desparasitaciones';

    /**
     * Dewormings can be recorded from the patient chart.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return DewormingForm::configure(
            $schema,
            includePatient: false,
            includeConsultation: false,
        );
    }

    public function table(Table $table): Table
    {
        return DewormingsTable::configure($table, showPatient: false)
            ->headerActions([
                CreateAction::make()
                    ->label('Registrar desparasitación')
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
