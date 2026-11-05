<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\Actions\InvoiceActions;
use App\Models\Invoice;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('issued_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('owner.full_name')
                    ->label('Tutor')
                    ->placeholder('-'),
                TextColumn::make('patient.name')
                    ->label('Paciente')
                    ->placeholder('Sin paciente')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('items_count')
                    ->label('Conceptos')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),
                TextColumn::make('total_cents')
                    ->label('Total')
                    ->formatStateUsing(fn (Invoice $record): string => $record->formattedTotal())
                    ->sortable(),
                TextColumn::make('paid_cents')
                    ->label('Pagado')
                    ->formatStateUsing(fn (Invoice $record): string => $record->formattedPaid())
                    ->toggleable(),
                TextColumn::make('balance_cents')
                    ->label('Saldo')
                    ->formatStateUsing(fn (Invoice $record): string => $record->formattedBalance())
                    ->color(fn (Invoice $record): string => $record->balance_cents > 0 ? 'warning' : 'success')
                    ->weight('bold')
                    ->sortable(),
            ])
            ->defaultSort('issued_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(InvoiceStatus::class)
                    ->multiple(),
                SelectFilter::make('owner_id')
                    ->label('Tutor')
                    ->relationship('owner', 'last_name')
                    ->searchable()
                    ->preload(),
                Filter::make('issued_at')
                    ->label('Rango de fechas')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Desde')
                            ->native(false),
                        DatePicker::make('until')
                            ->label('Hasta')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('issued_at', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('issued_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                InvoiceActions::recordPayment(),
                InvoiceActions::print(),
            ])
            ->emptyStateHeading('Sin recibos registrados')
            ->emptyStateDescription('Emita el primer recibo desde el botón superior.');
    }
}
