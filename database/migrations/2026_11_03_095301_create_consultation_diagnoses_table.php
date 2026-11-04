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
        Schema::create('consultation_diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('diagnosis_id')->nullable();
            $table->string('name', 255);
            $table->string('code', 30)->nullable();
            $table->string('type', 20)->default('primary');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['consultation_id', 'type']);
            $table->index('diagnosis_id');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_diagnoses');
    }
};
