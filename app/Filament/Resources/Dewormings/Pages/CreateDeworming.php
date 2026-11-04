<?php

namespace App\Filament\Resources\Dewormings\Pages;

use App\Filament\Resources\Dewormings\DewormingResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateDeworming extends CreateRecord
{
    protected static string $resource = DewormingResource::class;

    public function getTitle(): string
    {
        return 'Registrar desparasitación';
    }

    public function mount(): void
    {
        parent::mount();

        $patientId = request()->integer('patient_id');

        if ($patientId > 0) {
            $this->form->fill(['patient_id' => $patientId]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $data['created_by'] = auth()->id();

        return static::getModel()::create($data);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Desparasitación registrada';
    }
}
