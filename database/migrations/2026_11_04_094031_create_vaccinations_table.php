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
        Schema::create('vaccinations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vaccine_id')->constrained()->restrictOnDelete();
            $table->foreignId('vet_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('administered_at');
            $table->dateTime('next_due_at')->nullable();
            $table->string('batch_number', 100)->nullable();
            $table->string('manufacturer', 150)->nullable();
            $table->string('dose', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'administered_at']);
            $table->index('next_due_at');
            $table->index('vaccine_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vaccinations');
    }
};
