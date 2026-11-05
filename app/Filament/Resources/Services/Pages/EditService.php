<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Resources\Services\ServiceResource;
use App\Models\Service;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;

    public function getTitle(): string
    {
        return 'Editar servicio';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(function (): bool {
                    $record = $this->getRecord();

                    return $record instanceof Service && ! $record->invoiceItems()->exists();
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Servicio actualizado';
    }
}
