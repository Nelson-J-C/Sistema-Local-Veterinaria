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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->nullable()->unique();
            $table->string('name', 150);
            $table->string('category', 50)->default('general');
            $table->text('description')->nullable();
            $table->unsignedInteger('price_cents')->default(0);
            $table->decimal('tax_rate', 5, 2)->default(13.00);
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('category');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
