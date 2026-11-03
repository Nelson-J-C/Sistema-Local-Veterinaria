<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Enums\AppointmentStatus;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Patient;
use App\Services\AppointmentScheduler;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    public function getTitle(): string
    {
        return 'Nueva cita';
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
        $data['owner_id'] = Patient::query()->findOrFail($data['patient_id'])->owner_id;
        $data['created_by'] = auth()->id();
        $data['status'] = AppointmentStatus::Scheduled;

        return app(AppointmentScheduler::class)->book($data);
    }
}
