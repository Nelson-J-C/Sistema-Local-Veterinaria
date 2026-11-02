<?php

namespace App\Filament\Resources\Patients\RelationManagers;

use App\Enums\AlertSeverity;
use App\Enums\PatientAlertType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AlertsRelationManager extends RelationManager
{
    protected static string $relationship = 'alerts';

    protected static ?string $title = 'Alertas médicas';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Tipo de alerta')
                    ->options(PatientAlertType::class)
                    ->required(),
                TextInput::make('title')
                    ->label('Título')
                    ->maxLength(150)
                    ->required(),
                Select::make('severity')
                    ->label('Severidad')
                    ->options(AlertSeverity::class)
                    ->default(AlertSeverity::Medium)
                    ->required(),
                Toggle::make('active')
                    ->label('Alerta activa')
                    ->default(true),
                Textarea::make('description')
                    ->label('Descripción')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('severity')
                    ->label('Severidad')
                    ->badge(),
                IconColumn::make('active')
                    ->label('Activa')
                    ->boolean(),
                TextColumn::make('description')
                    ->label('Descripción')
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('createdBy.name')
                    ->label('Registrada por')
                    ->placeholder('Sistema')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Registrada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(PatientAlertType::class),
                SelectFilter::make('severity')
                    ->label('Severidad')
                    ->options(AlertSeverity::class),
                SelectFilter::make('active')
                    ->label('Estado')
                    ->options([
                        1 => 'Activas',
                        0 => 'Inactivas',
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nueva alerta')
                    ->mutateDataUsing(fn (array $data): array => [
                        ...$data,
                        'created_by' => auth()->id(),
                    ]),
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
            ->emptyStateHeading('Sin alertas médicas')
            ->emptyStateDescription('Registre alergias, condiciones crónicas o medicamentos importantes.');
    }
}
