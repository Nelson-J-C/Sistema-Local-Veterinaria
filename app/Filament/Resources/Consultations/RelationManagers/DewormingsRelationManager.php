<?php

namespace App\Filament\Resources\Consultations\RelationManagers;

use App\Filament\Resources\Dewormings\Schemas\DewormingForm;
use App\Filament\Resources\Dewormings\Tables\DewormingsTable;
use App\Models\Consultation;
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
     * Dewormings can be recorded during or after the consultation.
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
                        'patient_id' => $this->selectedConsultation()->patient_id,
                        'consultation_id' => $this->selectedConsultation()->getKey(),
                        'created_by' => auth()->id(),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
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
