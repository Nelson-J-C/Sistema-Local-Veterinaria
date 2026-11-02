<?php

namespace App\Filament\Resources\Activities\Tables;

use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('event')
                    ->label('Evento')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'created' => 'Creado',
                        'updated' => 'Actualizado',
                        'deleted' => 'Eliminado',
                        'restored' => 'Restaurado',
                        default => $state ?? 'Sin evento',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'restored' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('log_name')
                    ->label('Módulo')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('subject_type')
                    ->label('Registro')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-'),
                TextColumn::make('causer.name')
                    ->label('Usuario')
                    ->placeholder('Sistema')
                    ->searchable(),
                TextColumn::make('attribute_changes')
                    ->label('Cambios')
                    ->formatStateUsing(fn (mixed $state): string => self::formatProperties($state) ?? '-')
                    ->tooltip(fn (Activity $record): ?string => self::formatProperties($record->attribute_changes, pretty: true))
                    ->limit(60)
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Módulo')
                    ->options(fn (): array => Activity::query()
                        ->select('log_name')
                        ->distinct()
                        ->orderBy('log_name')
                        ->pluck('log_name', 'log_name')
                        ->all()),
                SelectFilter::make('event')
                    ->label('Evento')
                    ->options([
                        'created' => 'Creado',
                        'updated' => 'Actualizado',
                        'deleted' => 'Eliminado',
                        'restored' => 'Restaurado',
                    ]),
                SelectFilter::make('causer_id')
                    ->label('Usuario')
                    ->options(fn (): array => User::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable(),
            ])
            ->emptyStateHeading('Sin actividad registrada')
            ->emptyStateDescription('Los cambios auditados aparecerán aquí automáticamente.');
    }

    private static function formatProperties(mixed $state, bool $pretty = false): ?string
    {
        if (blank($state)) {
            return null;
        }

        $json = json_encode(
            $state,
            JSON_UNESCAPED_UNICODE | ($pretty ? JSON_PRETTY_PRINT : 0),
        );

        if ($json === false) {
            return null;
        }

        return $pretty ? $json : Str::limit($json, 60);
    }
}
