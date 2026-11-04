<?php

namespace App\Filament\Resources\Consultations\RelationManagers;

use App\Models\Consultation;
use App\Models\ConsultationAddendum;
use App\Services\ConsultationWorkflow;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AddendaRelationManager extends RelationManager
{
    protected static string $relationship = 'addenda';

    protected static ?string $title = 'Addenda';

    /**
     * Addenda are created on the view page, after the consultation is locked.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('content')
                    ->label('Contenido del addendum')
                    ->required()
                    ->minLength(5)
                    ->maxLength(5000)
                    ->rows(5)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('content')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('content')
                    ->label('Contenido')
                    ->wrap(),
                TextColumn::make('createdBy.name')
                    ->label('Registrado por')
                    ->placeholder('Sistema'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Agregar addendum')
                    ->visible(fn (): bool => $this->selectedConsultation()->isLocked())
                    ->using(function (array $data): ConsultationAddendum {
                        return app(ConsultationWorkflow::class)->addendum(
                            consultation: $this->selectedConsultation(),
                            content: (string) $data['content'],
                            userId: (int) auth()->id(),
                        );
                    }),
            ])
            ->emptyStateHeading('Sin addenda')
            ->emptyStateDescription('Las correcciones posteriores a la finalización se registran aquí.');
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
