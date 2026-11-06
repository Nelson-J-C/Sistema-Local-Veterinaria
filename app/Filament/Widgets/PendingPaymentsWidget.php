<?php

namespace App\Filament\Widgets;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PendingPaymentsWidget extends TableWidget
{
    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('invoices.view') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recibos por cobrar')
            ->description('Recibos emitidos con saldo pendiente.')
            ->query(fn (): Builder => Invoice::query()
                ->with(['owner', 'patient'])
                ->whereIn('status', [InvoiceStatus::Issued, InvoiceStatus::PartiallyPaid])
                ->where('balance_cents', '>', 0)
                ->orderBy('issued_at'))
            ->columns([
                TextColumn::make('number')
                    ->label('Número')
                    ->searchable(),
                TextColumn::make('owner.full_name')
                    ->label('Tutor'),
                TextColumn::make('issued_at')
                    ->label('Emitido')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('total_cents')
                    ->label('Total')
                    ->formatStateUsing(fn (Invoice $record): string => $record->formattedTotal()),
                TextColumn::make('balance_cents')
                    ->label('Saldo')
                    ->formatStateUsing(fn (Invoice $record): string => $record->formattedBalance())
                    ->color('warning')
                    ->weight('bold')
                    ->sortable(),
            ])
            ->recordUrl(fn (Invoice $record): string => InvoiceResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading('Sin saldos pendientes')
            ->emptyStateDescription('Todos los recibos emitidos están pagados.');
    }
}
