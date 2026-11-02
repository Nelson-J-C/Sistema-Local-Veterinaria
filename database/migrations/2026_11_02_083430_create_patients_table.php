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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->foreignId('species_id')->constrained()->restrictOnDelete();
            $table->foreignId('breed_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sex', 10)->default('unknown');
            $table->date('birth_date')->nullable();
            $table->string('color', 50)->nullable();
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->string('microchip', 50)->nullable()->unique();
            $table->boolean('sterilized')->default(false);
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('deceased_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'active']);
            $table->index(['species_id', 'active']);
            $table->index('name');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
