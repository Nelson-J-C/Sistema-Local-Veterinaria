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
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('medication_name', 255);
            $table->string('presentation', 100)->nullable();
            $table->string('dose', 100);
            $table->string('frequency', 100);
            $table->string('route', 20)->default('oral');
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->decimal('quantity', 8, 2)->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('dispensed')->default(false);
            $table->dateTime('dispensed_at')->nullable();
            $table->foreignId('dispensed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['consultation_id', 'dispensed']);
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
