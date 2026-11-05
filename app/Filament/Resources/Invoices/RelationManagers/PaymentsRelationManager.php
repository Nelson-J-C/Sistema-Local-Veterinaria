<?php

namespace App\Filament\Resources\Invoices\RelationManagers;

use App\Models\Payment;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Pagos';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['receivedBy', 'cashSession']))
            ->columns([
                TextColumn::make('paid_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('method')
                    ->label('Método')
                    ->badge(),
                TextColumn::make('reference')
                    ->label('Referencia')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('cashSession.id')
                    ->label('Caja')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('receivedBy.name')
                    ->label('Recibido por')
                    ->placeholder('-'),
                TextColumn::make('amount_cents')
                    ->label('Importe')
                    ->formatStateUsing(fn (Payment $record): string => $record->formattedAmount())
                    ->weight('bold'),
            ])
            ->defaultSort('paid_at', 'desc')
            ->emptyStateHeading('Sin pagos registrados')
            ->emptyStateDescription('Registre un pago desde el botón superior.');
    }
}
