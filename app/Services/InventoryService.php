<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Purchase;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    /**
     * Register a purchase, creating batches and stock movements for every line.
     *
     * @param  array{
     *     supplier_id?: int|null,
     *     document_number?: string|null,
     *     purchased_at?: string|\DateTimeInterface|null,
     *     notes?: string|null,
     *     items: array<int, array{
     *         product_id: int,
     *         quantity: float|int|string,
     *         unit_cost_cents: float|int|string,
     *         batch_number?: string|null,
     *         expires_at?: string|null
     *     }>
     * }  $data
     */
    public function receivePurchase(array $data, int $userId): Purchase
    {
        return DB::transaction(function () use ($data, $userId): Purchase {
            $purchase = Purchase::query()->create([
                'supplier_id' => $data['supplier_id'] ?? null,
                'document_number' => $data['document_number'] ?? null,
                'purchased_at' => Carbon::parse($data['purchased_at'] ?? today()),
                'subtotal_cents' => 0,
                'tax_cents' => 0,
                'total_cents' => 0,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            $subtotal = 0;
            $tax = 0;

            foreach ($data['items'] as $item) {
                $quantity = round((float) $item['quantity'], 3);
                $unitCost = (int) round((float) $item['unit_cost_cents']);

                if ($quantity <= 0) {
                    throw ValidationException::withMessages([
                        'items' => 'Cada línea de compra debe tener una cantidad mayor a cero.',
                    ]);
                }

                $product = Product::query()
                    ->lockForUpdate()
                    ->findOrFail($item['product_id']);

                $lineTotal = (int) round($quantity * $unitCost);
                $subtotal += $lineTotal;
                $tax += (int) round($lineTotal * ((float) $product->tax_rate) / 100);

                $purchase->items()->create([
                    'product_id' => $product->getKey(),
                    'batch_number' => $item['batch_number'] ?? null,
                    'expires_at' => $item['expires_at'] ?? null,
                    'quantity' => $quantity,
                    'unit_cost_cents' => $unitCost,
                    'line_total_cents' => $lineTotal,
                ]);

                if ($product->track_stock) {
                    $batch = ProductBatch::query()->create([
                        'product_id' => $product->getKey(),
                        'batch_number' => $this->batchNumberFor($item['batch_number'] ?? null),
                        'expires_at' => $item['expires_at'] ?? null,
                        'quantity_received' => $quantity,
                        'quantity_remaining' => $quantity,
                        'unit_cost_cents' => $unitCost,
                        'supplier_id' => $data['supplier_id'] ?? null,
                        'received_at' => $purchase->purchased_at,
                    ]);

                    $this->recordMovement(
                        product: $product,
                        batch: $batch,
                        type: StockMovementType::Purchase,
                        quantity: $quantity,
                        unitCost: $unitCost,
                        userId: $userId,
                        reference: $purchase,
                        occurredAt: now(),
                    );

                    $product->stock_quantity = (float) $product->stock_quantity + $quantity;
                }

                $product->cost_cents = $unitCost;
                $product->save();
            }

            $purchase->update([
                'subtotal_cents' => $subtotal,
                'tax_cents' => $tax,
                'total_cents' => $subtotal + $tax,
            ]);

            return $purchase->refresh();
        });
    }

    /**
     * Adjust the stock of a product manually.
     *
     * A positive quantity creates a batch (or adds to the given one). A negative
     * quantity is taken from the oldest expiring batches first.
     *
     * @param  array{
     *     quantity: float|int|string,
     *     notes: string,
     *     batch_number?: string|null,
     *     expires_at?: string|null,
     *     unit_cost_cents?: float|int|string|null
     * }  $data
     * @return array<int, StockMovement>
     */
    public function adjust(Product $product, array $data, int $userId): array
    {
        return DB::transaction(function () use ($product, $data, $userId): array {
            $product = Product::query()->lockForUpdate()->findOrFail($product->getKey());
            $quantity = round((float) $data['quantity'], 3);

            if ($quantity === 0.0) {
                throw ValidationException::withMessages([
                    'quantity' => 'La cantidad del ajuste no puede ser cero.',
                ]);
            }

            if ($quantity < 0) {
                return $this->consume(
                    product: $product,
                    quantity: abs($quantity),
                    type: StockMovementType::Adjustment,
                    userId: $userId,
                    notes: $data['notes'],
                );
            }

            $unitCost = (int) round((float) ($data['unit_cost_cents'] ?? $product->cost_cents));

            $batch = ProductBatch::query()->create([
                'product_id' => $product->getKey(),
                'batch_number' => $this->batchNumberFor($data['batch_number'] ?? null),
                'expires_at' => $data['expires_at'] ?? null,
                'quantity_received' => $quantity,
                'quantity_remaining' => $quantity,
                'unit_cost_cents' => $unitCost,
                'supplier_id' => $product->supplier_id,
                'received_at' => today(),
                'notes' => $data['notes'],
            ]);

            $movement = $this->recordMovement(
                product: $product,
                batch: $batch,
                type: StockMovementType::Adjustment,
                quantity: $quantity,
                unitCost: $unitCost,
                userId: $userId,
                notes: $data['notes'],
                occurredAt: now(),
            );

            $product->stock_quantity = (float) $product->stock_quantity + $quantity;
            $product->save();

            return [$movement];
        });
    }

    /**
     * Remove stock from a product using FEFO (first expiry, first out).
     *
     * @return array<int, StockMovement>
     */
    public function consume(
        Product $product,
        float $quantity,
        StockMovementType $type,
        int $userId,
        ?Model $reference = null,
        ?string $notes = null,
    ): array {
        return DB::transaction(function () use ($product, $quantity, $type, $userId, $reference, $notes): array {
            $product = Product::query()->lockForUpdate()->findOrFail($product->getKey());
            $quantity = round($quantity, 3);

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'La cantidad a descontar debe ser mayor a cero.',
                ]);
            }

            if (! $product->track_stock) {
                throw ValidationException::withMessages([
                    'quantity' => 'Este producto no controla stock, no se puede descontar.',
                ]);
            }

            $batches = $this->fefoBatches($product);

            $available = $batches->sum(fn (ProductBatch $batch): float => (float) $batch->quantity_remaining);

            if ($available < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => sprintf(
                        'Stock insuficiente de %s: disponible %s, solicitado %s.',
                        $product->name,
                        Format::quantity($available),
                        Format::quantity($quantity),
                    ),
                ]);
            }

            $remaining = $quantity;
            $movements = [];

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $take = min($remaining, (float) $batch->quantity_remaining);

                $batch->quantity_remaining = (float) $batch->quantity_remaining - $take;
                $batch->save();

                $movements[] = $this->recordMovement(
                    product: $product,
                    batch: $batch,
                    type: $type,
                    quantity: -$take,
                    unitCost: $batch->unit_cost_cents,
                    userId: $userId,
                    reference: $reference,
                    notes: $notes,
                    occurredAt: now(),
                );

                $remaining = round($remaining - $take, 3);
            }

            $product->stock_quantity = (float) $product->stock_quantity - $quantity;
            $product->save();

            return $movements;
        });
    }

    /**
     * Consumable batches ordered by expiry date, then by reception date.
     *
     * @return Collection<int, ProductBatch>
     */
    public function fefoBatches(Product $product): Collection
    {
        return ProductBatch::query()
            ->where('product_id', $product->getKey())
            ->usable()
            ->orderByRaw('expires_at IS NULL')
            ->orderBy('expires_at')
            ->orderBy('received_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * Stock available for dispensing, ignoring expired batches.
     */
    public function availableQuantity(Product $product): float
    {
        return (float) ProductBatch::query()
            ->where('product_id', $product->getKey())
            ->usable()
            ->sum('quantity_remaining');
    }

    /**
     * Store an immutable stock movement row.
     */
    private function recordMovement(
        Product $product,
        ?ProductBatch $batch,
        StockMovementType $type,
        float $quantity,
        ?int $unitCost,
        int $userId,
        ?Model $reference = null,
        ?string $notes = null,
        ?Carbon $occurredAt = null,
    ): StockMovement {
        return StockMovement::query()->create([
            'product_id' => $product->getKey(),
            'batch_id' => $batch?->getKey(),
            'type' => $type,
            'quantity' => round($quantity, 3),
            'unit_cost_cents' => $unitCost,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'notes' => $notes,
            'user_id' => $userId,
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }

    /**
     * Fallback batch number for manual entries.
     */
    private function batchNumberFor(?string $batchNumber): string
    {
        $batchNumber = trim((string) $batchNumber);

        return $batchNumber !== '' ? $batchNumber : 'AJUSTE-'.now()->format('YmdHis');
    }
}
