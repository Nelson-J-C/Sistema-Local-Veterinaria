<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description', 255);
            $table->decimal('quantity', 12, 3);
            $table->unsignedBigInteger('unit_price_cents');
            $table->unsignedBigInteger('discount_cents')->default(0);
            $table->decimal('tax_rate', 5, 2)->default(13);
            $table->unsignedBigInteger('line_subtotal_cents');
            $table->unsignedBigInteger('line_tax_cents');
            $table->unsignedBigInteger('line_total_cents');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('invoice_id');
            $table->index('product_id');
            $table->index('service_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
