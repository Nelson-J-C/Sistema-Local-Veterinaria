<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Models\Owner;
use App\Models\Patient;
use App\Models\Product;
use App\Models\Service;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del recibo')
                    ->columns(2)
                    ->schema([
                        Select::make('owner_id')
                            ->label('Tutor')
                            ->options(fn (): array => Owner::query()
                                ->orderBy('last_name')
                                ->orderBy('first_name')
                                ->get()
                                ->mapWithKeys(fn (Owner $owner): array => [$owner->getKey() => $owner->full_name])
                                ->all())
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('patient_id', null)),
                        Select::make('patient_id')
                            ->label('Paciente')
                            ->options(fn (Get $get): array => Patient::query()
                                ->where('owner_id', $get('owner_id'))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->disabled(fn (Get $get): bool => blank($get('owner_id')))
                            ->helperText('Seleccione primero el tutor.'),
                        DatePicker::make('issued_at')
                            ->label('Fecha de emisión')
                            ->required()
                            ->default(fn (): string => now()->toDateString())
                            ->native(false),
                        DatePicker::make('due_at')
                            ->label('Vencimiento')
                            ->native(false)
                            ->helperText('Opcional.'),
                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                Section::make('Conceptos')
                    ->description('Puede cobrar servicios, productos o conceptos manuales. El IVA y los totales se calculan al guardar.')
                    ->schema([
                        Repeater::make('items')
                            ->label('Conceptos del recibo')
                            ->addActionLabel('Agregar concepto')
                            ->minItems(1)
                            ->defaultItems(1)
                            ->columns(2)
                            ->schema([
                                Select::make('catalog_item')
                                    ->label('Servicio o producto')
                                    ->options(fn (): array => self::catalogOptions())
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function (?string $state, Set $set): void {
                                        [$description, $priceCents, $taxRate] = self::catalogValues($state);

                                        $set('description', $description);
                                        $set('unit_price_cents', round($priceCents / 100, 2));
                                        $set('tax_rate', $taxRate);
                                    })
                                    ->columnSpanFull(),
                                TextInput::make('description')
                                    ->label('Descripción')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('quantity')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0.001)
                                    ->step(0.001)
                                    ->default(1)
                                    ->live(onBlur: true),
                                TextInput::make('unit_price_cents')
                                    ->label('Precio unitario')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->prefix(config('clinic.currency_symbol'))
                                    ->formatStateUsing(fn (mixed $state): float => round(((float) $state) / 100, 2))
                                    ->dehydrateStateUsing(fn (mixed $state): int => (int) round(((float) $state) * 100))
                                    ->live(onBlur: true),
                                TextInput::make('discount_cents')
                                    ->label('Descuento')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->prefix(config('clinic.currency_symbol'))
                                    ->default(0)
                                    ->formatStateUsing(fn (mixed $state): float => round(((float) $state) / 100, 2))
                                    ->dehydrateStateUsing(fn (mixed $state): int => (int) round(((float) $state) * 100))
                                    ->live(onBlur: true),
                                TextInput::make('tax_rate')
                                    ->label('IVA')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.01)
                                    ->suffix('%')
                                    ->default(fn (): float => (float) config('clinic.tax_rate'))
                                    ->live(onBlur: true),
                            ]),
                        TextEntry::make('totals_preview')
                            ->label('Resumen')
                            ->state(fn (Get $get): string => self::preview($get('items') ?? []))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Grouped options for the service and product catalog.
     *
     * @return array<string, array<string, string>>
     */
    private static function catalogOptions(): array
    {
        $services = Service::query()
            ->active()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Service $service): array => [
                'service:'.$service->getKey() => $service->name.' · '.$service->formattedPrice(),
            ])
            ->all();

        $products = Product::query()
            ->active()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Product $product): array => [
                'product:'.$product->getKey() => $product->name.' · '.$product->formattedPrice(),
            ])
            ->all();

        return array_filter([
            'Servicios' => $services,
            'Productos' => $products,
        ]);
    }

    /**
     * Description, price in cents and tax rate for a catalog selection.
     *
     * @return array{0: string, 1: int, 2: float}
     */
    private static function catalogValues(?string $catalogItem): array
    {
        if ($catalogItem !== null && str_contains($catalogItem, ':')) {
            [$type, $id] = explode(':', $catalogItem, 2);

            if ($type === 'service' && ctype_digit($id)) {
                $service = Service::query()->find((int) $id);

                if ($service !== null) {
                    return [$service->name, (int) $service->price_cents, (float) $service->tax_rate];
                }
            }

            if ($type === 'product' && ctype_digit($id)) {
                $product = Product::query()->find((int) $id);

                if ($product !== null) {
                    return [$product->name, (int) $product->price_cents, (float) $product->tax_rate];
                }
            }
        }

        return ['', 0, (float) config('clinic.tax_rate')];
    }

    /**
     * Readable preview of the receipt totals based on the current form state.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private static function preview(array $items): string
    {
        $subtotal = 0.0;
        $discount = 0.0;
        $tax = 0.0;

        foreach ($items as $item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price_cents'] ?? 0);
            $lineDiscount = (float) ($item['discount_cents'] ?? 0);
            $taxRate = (float) ($item['tax_rate'] ?? 0);

            $lineSubtotal = $quantity * $unitPrice;
            $lineDiscount = min($lineDiscount, $lineSubtotal);

            $subtotal += $lineSubtotal;
            $discount += $lineDiscount;
            $tax += ($lineSubtotal - $lineDiscount) * $taxRate / 100;
        }

        $symbol = config('clinic.currency_symbol');
        $total = $subtotal - $discount + $tax;

        return sprintf(
            'Subtotal: %s %s · Descuento: %s %s · IVA: %s %s · Total: %s %s',
            $symbol,
            number_format($subtotal, 2, '.', ','),
            $symbol,
            number_format($discount, 2, '.', ','),
            $symbol,
            number_format($tax, 2, '.', ','),
            $symbol,
            number_format($total, 2, '.', ','),
        );
    }
}
