<?php

namespace App\Filament\Resources\Consultations\Pages;

use App\Filament\Resources\Consultations\Actions\ConsultationActions;
use App\Filament\Resources\Consultations\ConsultationResource;
use App\Models\Consultation;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditConsultation extends EditRecord
{
    protected static string $resource = ConsultationResource::class;

    public function getTitle(): string
    {
        return 'Editar consulta';
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        /** @var Consultation $consultation */
        $consultation = $this->getRecord();

        abort_if($consultation->isLocked(), 403, 'La consulta está finalizada y solo admite addenda.');
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            ConsultationActions::finalize(),
            ConsultationActions::addendum(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Consultation $record */
        abort_if($record->isLocked(), 403, 'La consulta está finalizada y solo admite addenda.');

        $record->update($data);

        return $record;
    }
}
