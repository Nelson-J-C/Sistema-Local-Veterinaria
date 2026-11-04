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
        Schema::create('dewormings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('product_name', 150);
            $table->foreignId('vet_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('administered_at');
            $table->dateTime('next_due_at')->nullable();
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->string('dose', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'administered_at']);
            $table->index('next_due_at');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dewormings');
    }
};
