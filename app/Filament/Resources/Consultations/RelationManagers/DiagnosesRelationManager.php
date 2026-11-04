<?php

namespace App\Filament\Resources\Consultations\RelationManagers;

use App\Enums\DiagnosisType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DiagnosesRelationManager extends RelationManager
{
    protected static string $relationship = 'diagnoses';

    protected static ?string $title = 'Diagnósticos';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Diagnóstico')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Diagnóstico')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('code')
                            ->label('Código')
                            ->maxLength(30)
                            ->placeholder('Opcional'),
                        Select::make('type')
                            ->label('Tipo')
                            ->options(DiagnosisType::class)
                            ->default(DiagnosisType::Primary)
                            ->required(),
                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('name')
                    ->label('Diagnóstico')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('code')
                    ->label('Código')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('notes')
                    ->label('Notas')
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('type')
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(DiagnosisType::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Agregar diagnóstico'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Sin diagnósticos registrados')
            ->emptyStateDescription('Registre el diagnóstico principal y los secundarios.');
    }
}
