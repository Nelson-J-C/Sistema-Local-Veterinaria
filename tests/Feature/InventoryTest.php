<?php

namespace Tests\Feature;

use App\Enums\ProductUnit;
use App\Enums\StockMovementType;
use App\Filament\Resources\Consultations\Pages\EditConsultation;
use App\Filament\Resources\Consultations\RelationManagers\PrescriptionsRelationManager;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Purchases\Pages\CreatePurchase;
use App\Filament\Resources\Purchases\Pages\ListPurchases;
use App\Filament\Resources\Purchases\PurchaseResource;
use App\Filament\Widgets\ExpiringBatchesWidget;
use App\Filament\Widgets\LowStockWidget;
use App\Models\Consultation;
use App\Models\Owner;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductCategory;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InventoryService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $warehouse;

    private User $vet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        Filament::setCurrentPanel('admin');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->warehouse = User::factory()->create();
        $this->warehouse->assignRole('almacen');

        $this->vet = User::factory()->create();
        $this->vet->assignRole('veterinario');

        $this->actingAs($this->admin);
    }

    public function test_administrators_and_warehouse_users_can_open_the_inventory(): void
    {
        $this->get(ListProducts::getUrl())->assertOk();
        $this->get(ListPurchases::getUrl())->assertOk();

        $this->actingAs($this->warehouse)
            ->get(ListProducts::getUrl())
            ->assertOk();

        $this->actingAs($this->warehouse)
            ->get(CreatePurchase::getUrl())
            ->assertOk();
    }

    public function test_the_product_and_purchase_detail_pages_render(): void
    {
        $product = Product::factory()->create();
        $this->adjust($product, 4, 'LOTE-DETALLE', today()->addMonth());

        $this->get(ProductResource::getUrl('view', ['record' => $product]))->assertOk();

        $purchase = $this->purchaseFor($product, quantity: 2);

        $this->get(PurchaseResource::getUrl('view', ['record' => $purchase]))->assertOk();
    }

    public function test_stock_can_be_adjusted_from_the_product_page(): void
    {
        $product = Product::factory()->create(['cost_cents' => 500]);

        Livewire::test(ViewProduct::class, ['record' => $product->getKey()])
            ->callAction('adjustStock', [
                'quantity' => 7,
                'batch_number' => 'AJUSTE-WEB',
                'expires_at' => today()->addMonths(6)->toDateString(),
                'unit_cost' => 6.5,
                'notes' => 'Conteo físico mensual',
            ])
            ->assertNotified();

        $product->refresh();

        $this->assertSame(7.0, (float) $product->stock_quantity);
        $this->assertDatabaseHas('product_batches', [
            'product_id' => $product->getKey(),
            'batch_number' => 'AJUSTE-WEB',
            'unit_cost_cents' => 650,
        ]);
    }

    public function test_roles_without_inventory_permissions_cannot_manage_products(): void
    {
        $this->actingAs($this->vet)
            ->get(ListProducts::getUrl())
            ->assertOk();

        $this->actingAs($this->vet)
            ->get(CreateProduct::getUrl())
            ->assertForbidden();

        $client = User::factory()->create();
        $client->assignRole('cliente');

        $this->actingAs($client)
            ->get(ListProducts::getUrl())
            ->assertForbidden();
    }

    public function test_a_product_can_be_registered_from_the_panel(): void
    {
        $category = ProductCategory::factory()->create();
        $supplier = Supplier::factory()->create();

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'sku' => 'MED-001',
                'barcode' => '7771234567890',
                'name' => 'Amoxicilina 250 mg',
                'category_id' => $category->getKey(),
                'supplier_id' => $supplier->getKey(),
                'unit' => ProductUnit::Unit->value,
                'presentation' => 'Caja por 20 tabletas',
                'concentration' => '250 mg',
                'cost_cents' => 12.5,
                'price_cents' => 20,
                'tax_rate' => 13,
                'track_stock' => true,
                'min_stock' => 5,
                'max_stock' => 100,
                'expiry_alert_days' => 60,
                'active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('products', [
            'sku' => 'MED-001',
            'barcode' => '7771234567890',
            'cost_cents' => 1250,
            'price_cents' => 2000,
            'category_id' => $category->getKey(),
            'supplier_id' => $supplier->getKey(),
        ]);
    }

    public function test_a_purchase_creates_batches_movements_and_updates_stock(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create(['cost_cents' => 800, 'tax_rate' => 13]);

        Livewire::test(CreatePurchase::class)
            ->fillForm([
                'supplier_id' => $supplier->getKey(),
                'document_number' => 'FAC-100',
                'purchased_at' => today()->toDateString(),
                'items' => [
                    [
                        'product_id' => $product->getKey(),
                        'batch_number' => 'LOTE-A',
                        'expires_at' => today()->addMonths(8)->toDateString(),
                        'quantity' => 5,
                        'unit_cost_cents' => 10,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $purchase = Purchase::query()->firstOrFail();
        $product->refresh();

        $this->assertSame(5000, $purchase->subtotal_cents);
        $this->assertSame(650, $purchase->tax_cents);
        $this->assertSame(5650, $purchase->total_cents);
        $this->assertSame(5.0, (float) $product->stock_quantity);
        $this->assertSame(1000, $product->cost_cents);

        $batch = ProductBatch::query()->firstOrFail();

        $this->assertSame('LOTE-A', $batch->batch_number);
        $this->assertSame(5.0, (float) $batch->quantity_remaining);

        $movement = StockMovement::query()->firstOrFail();

        $this->assertSame(StockMovementType::Purchase, $movement->type);
        $this->assertSame(5.0, (float) $movement->quantity);
        $this->assertSame($purchase->getKey(), $movement->reference_id);
    }

    public function test_a_purchase_appears_in_the_purchase_list(): void
    {
        $purchase = $this->purchaseFor($this->product(), quantity: 3);

        Livewire::test(ListPurchases::class)
            ->assertCanSeeTableRecords([$purchase]);
    }

    public function test_fefo_consumes_the_earliest_expiring_batch_first(): void
    {
        $product = Product::factory()->create();

        $this->adjust($product, 4, 'LOTE-VIEJO', today()->addDays(5));
        $this->adjust($product, 10, 'LOTE-NUEVO', today()->addDays(60));

        $movements = app(InventoryService::class)->consume(
            product: $product,
            quantity: 6,
            type: StockMovementType::Sale,
            userId: $this->admin->getKey(),
            notes: 'Venta de mostrador',
        );

        $product->refresh();

        $this->assertSame(8.0, (float) $product->stock_quantity);
        $this->assertCount(2, $movements);

        $oldBatch = ProductBatch::query()->where('batch_number', 'LOTE-VIEJO')->firstOrFail();
        $newBatch = ProductBatch::query()->where('batch_number', 'LOTE-NUEVO')->firstOrFail();

        $this->assertSame(0.0, (float) $oldBatch->quantity_remaining);
        $this->assertSame(8.0, (float) $newBatch->quantity_remaining);
        $this->assertSame('LOTE-VIEJO', $movements[0]->loadMissing('batch')->batch?->batch_number);
    }

    public function test_dispensing_more_than_available_is_rejected(): void
    {
        $product = Product::factory()->create();
        $this->adjust($product, 3, 'LOTE-A', today()->addMonth());

        $this->expectException(ValidationException::class);

        app(InventoryService::class)->consume(
            product: $product,
            quantity: 5,
            type: StockMovementType::Sale,
            userId: $this->admin->getKey(),
        );
    }

    public function test_expired_batches_are_not_dispensed(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        ProductBatch::factory()->expired()->create([
            'product_id' => $product->getKey(),
            'quantity_received' => 10,
            'quantity_remaining' => 10,
        ]);

        $this->expectException(ValidationException::class);

        app(InventoryService::class)->consume(
            product: $product,
            quantity: 1,
            type: StockMovementType::Sale,
            userId: $this->admin->getKey(),
        );
    }

    public function test_a_manual_adjustment_creates_a_batch_and_a_movement(): void
    {
        $product = Product::factory()->create(['cost_cents' => 900]);

        $this->adjust($product, 12, 'CARGA-INICIAL', today()->addYear(), 'Carga inicial de inventario');

        $product->refresh();

        $this->assertSame(12.0, (float) $product->stock_quantity);
        $this->assertDatabaseHas('product_batches', [
            'product_id' => $product->getKey(),
            'batch_number' => 'CARGA-INICIAL',
            'quantity_remaining' => 12,
            'unit_cost_cents' => 900,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->getKey(),
            'type' => StockMovementType::Adjustment->value,
            'quantity' => 12,
            'notes' => 'Carga inicial de inventario',
        ]);
    }

    public function test_a_negative_adjustment_cannot_leave_negative_stock(): void
    {
        $product = Product::factory()->create();
        $this->adjust($product, 3, 'LOTE-A', today()->addMonth());

        try {
            app(InventoryService::class)->adjust($product, [
                'quantity' => -5,
                'notes' => 'Conteo físico',
            ], $this->admin->getKey());

            $this->fail('A negative adjustment above the available stock should fail.');
        } catch (ValidationException) {
            // Expected.
        }

        $product->refresh();

        $this->assertSame(3.0, (float) $product->stock_quantity);
    }

    public function test_products_without_stock_control_do_not_create_batches_or_movements(): void
    {
        $product = Product::factory()->withoutStockControl()->create();

        $purchase = $this->purchaseFor($product, quantity: 3, unitCost: 1500);

        $product->refresh();

        $this->assertSame(0.0, (float) $product->stock_quantity);
        $this->assertSame(1500, $product->cost_cents);
        $this->assertSame(0, $product->batches()->count());
        $this->assertSame(0, $product->movements()->count());
        $this->assertSame(4500, $purchase->subtotal_cents);
    }

    public function test_low_stock_scope_and_widget_only_show_products_needing_stock(): void
    {
        $low = Product::factory()->lowStock()->create();
        $empty = Product::factory()->withStock(0, 0)->create();
        $healthy = Product::factory()->withStock(50, 10)->create();

        $this->assertTrue($low->isLowStock());
        $this->assertTrue($empty->isOutOfStock());
        $this->assertFalse($healthy->isLowStock());

        Livewire::test(LowStockWidget::class)
            ->assertCanSeeTableRecords([$low, $empty])
            ->assertCanNotSeeTableRecords([$healthy]);
    }

    public function test_expiring_batches_widget_lists_batches_inside_the_alert_window(): void
    {
        $product = Product::factory()->create();

        $soon = ProductBatch::factory()->expiringSoon(10)->create(['product_id' => $product->getKey()]);
        $expired = ProductBatch::factory()->expired()->create(['product_id' => $product->getKey()]);
        $later = ProductBatch::factory()->create([
            'product_id' => $product->getKey(),
            'expires_at' => today()->addYear(),
        ]);

        Livewire::test(ExpiringBatchesWidget::class)
            ->assertCanSeeTableRecords([$soon, $expired])
            ->assertCanNotSeeTableRecords([$later]);
    }

    public function test_prescriptions_dispense_stock_with_fefo(): void
    {
        $product = Product::factory()->create();
        $this->adjust($product, 5, 'LOTE-RECETA', today()->addDays(20));

        $consultation = $this->consultation();
        $prescription = Prescription::factory()->create([
            'consultation_id' => $consultation->getKey(),
            'product_id' => $product->getKey(),
            'quantity' => 2,
            'dispensed' => false,
        ]);

        Livewire::test(PrescriptionsRelationManager::class, [
            'ownerRecord' => $consultation,
            'pageClass' => EditConsultation::class,
        ])
            ->callAction(TestAction::make('dispense')->table($prescription))
            ->assertNotified();

        $product->refresh();
        $prescription->refresh();

        $this->assertSame(3.0, (float) $product->stock_quantity);
        $this->assertTrue($prescription->dispensed);
        $this->assertNotNull($prescription->dispensed_at);
        $this->assertSame($this->admin->getKey(), $prescription->dispensed_by);

        $movement = StockMovement::query()
            ->where('reference_type', $prescription->getMorphClass())
            ->where('reference_id', $prescription->getKey())
            ->firstOrFail();

        $this->assertSame(StockMovementType::Sale, $movement->type);
        $this->assertSame(-2.0, (float) $movement->quantity);
    }

    public function test_products_with_movements_cannot_be_deleted(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->warehouse);

        $this->assertTrue($this->warehouse->can('delete', $product));

        $this->adjust($product, 1, 'LOTE-A', today()->addMonth());

        $this->assertFalse($this->warehouse->can('delete', $product->refresh()));
    }

    public function test_categories_and_suppliers_with_records_cannot_be_deleted(): void
    {
        $category = ProductCategory::factory()->create();
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->getKey(),
            'supplier_id' => $supplier->getKey(),
        ]);

        $this->purchaseFor($product, quantity: 1);

        $this->actingAs($this->warehouse);

        $this->assertFalse($this->warehouse->can('delete', $category->refresh()));
        $this->assertFalse($this->warehouse->can('delete', $supplier->refresh()));
    }

    /**
     * Create a product with default attributes.
     */
    private function product(): Product
    {
        return Product::factory()->create();
    }

    /**
     * Register an incoming stock adjustment.
     */
    private function adjust(
        Product $product,
        float $quantity,
        string $batchNumber,
        mixed $expiresAt,
        string $notes = 'Carga de inventario',
    ): void {
        app(InventoryService::class)->adjust($product, [
            'quantity' => $quantity,
            'batch_number' => $batchNumber,
            'expires_at' => $expiresAt,
            'notes' => $notes,
        ], $this->admin->getKey());
    }

    /**
     * Register a purchase for a single product line.
     */
    private function purchaseFor(Product $product, float $quantity, int $unitCost = 1000): Purchase
    {
        return app(InventoryService::class)->receivePurchase([
            'supplier_id' => $product->supplier_id,
            'document_number' => 'FAC-'.fake()->numerify('###'),
            'purchased_at' => today()->toDateString(),
            'items' => [
                [
                    'product_id' => $product->getKey(),
                    'batch_number' => 'COMPRA-'.fake()->numerify('###'),
                    'expires_at' => today()->addYear()->toDateString(),
                    'quantity' => $quantity,
                    'unit_cost_cents' => $unitCost,
                ],
            ],
        ], $this->admin->getKey());
    }

    /**
     * Create a draft consultation for the default patient.
     */
    private function consultation(): Consultation
    {
        $owner = Owner::factory()->create();
        $patient = Patient::factory()->create(['owner_id' => $owner->getKey()]);

        return Consultation::factory()->create([
            'patient_id' => $patient->getKey(),
            'vet_id' => $this->vet->getKey(),
            'created_by' => $this->admin->getKey(),
        ]);
    }
}
