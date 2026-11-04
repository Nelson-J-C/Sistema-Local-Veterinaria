<?php

namespace App\Filament\Resources\Consultations\RelationManagers;

use App\Models\Attachment;
use App\Models\Consultation;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $title = 'Adjuntos';

    /**
     * Laboratory results and other documents may arrive after finalization.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('path')
                    ->label('Archivo')
                    ->disk('local')
                    ->directory('attachments')
                    ->storeFileNamesIn('original_name')
                    ->required()
                    ->maxSize(10240)
                    ->acceptedFileTypes([
                        'application/pdf',
                        'image/jpeg',
                        'image/png',
                        'image/webp',
                    ])
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->label('Notas')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_name')
            ->columns([
                TextColumn::make('original_name')
                    ->label('Archivo')
                    ->searchable()
                    ->url(fn (Attachment $record): string => route('attachments.download', $record))
                    ->openUrlInNewTab(),
                TextColumn::make('mime_type')
                    ->label('Tipo')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('size')
                    ->label('Tamaño')
                    ->formatStateUsing(fn (int $state): string => number_format($state / 1024, 1).' KB')
                    ->toggleable(),
                TextColumn::make('uploadedBy.name')
                    ->label('Subido por')
                    ->placeholder('Sistema'),
                TextColumn::make('created_at')
                    ->label('Subido')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Notas')
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Subir archivo')
                    ->mutateDataUsing(function (array $data): array {
                        /** @var Consultation $consultation */
                        $consultation = $this->getOwnerRecord();
                        $path = $data['path'] ?? null;

                        return [
                            ...$data,
                            'patient_id' => $consultation->patient_id,
                            'uploaded_by' => auth()->id(),
                            'mime_type' => filled($path) ? Storage::disk('local')->mimeType($path) : null,
                            'size' => filled($path) ? (Storage::disk('local')->size($path) ?: 0) : 0,
                        ];
                    }),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->visible(fn (): bool => $this->selectedConsultation()->isDraft())
                    ->authorize(fn (Attachment $record): bool => $this->selectedConsultation()->isDraft()
                        && (auth()->user()?->can('delete', $record) ?? false)),
            ])
            ->emptyStateHeading('Sin archivos adjuntos')
            ->emptyStateDescription('Adjunte resultados de laboratorio, imágenes o documentos clínicos.');
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
