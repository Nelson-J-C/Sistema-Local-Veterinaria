<?php

namespace App\Filament\Resources\Consultations\Actions;

use App\Filament\Resources\Consultations\ConsultationResource;
use App\Models\Consultation;
use App\Services\ConsultationWorkflow;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class ConsultationActions
{
    public static function finalize(): Action
    {
        return Action::make('finalize')
            ->label('Finalizar consulta')
            ->icon(Heroicon::OutlinedLockClosed)
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Finalizar consulta')
            ->modalDescription('Al finalizar, la consulta quedará bloqueada. Las correcciones posteriores se registran como addendum.')
            ->modalSubmitActionLabel('Finalizar')
            ->visible(fn (Consultation $record): bool => $record->isDraft())
            ->authorize(fn (Consultation $record): bool => auth()->user()?->can('finalize', $record) ?? false)
            ->action(function (Consultation $record, Action $action): void {
                app(ConsultationWorkflow::class)->finalize($record, (int) auth()->id());

                Notification::make()
                    ->success()
                    ->title('Consulta finalizada')
                    ->body('El registro clínico quedó bloqueado.')
                    ->send();

                self::refreshView($action, $record);
            });
    }

    public static function addendum(): Action
    {
        return Action::make('addendum')
            ->label('Agregar addendum')
            ->icon(Heroicon::OutlinedDocumentPlus)
            ->color('info')
            ->modalHeading('Addendum de la consulta')
            ->modalDescription('Registre una corrección o aclaración posterior a la finalización.')
            ->modalSubmitActionLabel('Guardar addendum')
            ->schema([
                Textarea::make('content')
                    ->label('Contenido del addendum')
                    ->required()
                    ->minLength(5)
                    ->maxLength(5000)
                    ->rows(5)
                    ->columnSpanFull(),
            ])
            ->visible(fn (Consultation $record): bool => $record->isLocked())
            ->authorize(fn (Consultation $record): bool => auth()->user()?->can('addendum', $record) ?? false)
            ->action(function (Consultation $record, Action $action, array $data): void {
                app(ConsultationWorkflow::class)->addendum(
                    consultation: $record,
                    content: (string) $data['content'],
                    userId: (int) auth()->id(),
                );

                Notification::make()
                    ->success()
                    ->title('Addendum registrado')
                    ->send();

                self::refreshView($action, $record);
            });
    }

    /**
     * Reload the consultation view after changing its status.
     */
    private static function refreshView(Action $action, Consultation $record): void
    {
        $livewire = $action->getLivewire();

        if (method_exists($livewire, 'redirect')) {
            $livewire->redirect(ConsultationResource::getUrl('view', ['record' => $record]));
        }
    }
}
