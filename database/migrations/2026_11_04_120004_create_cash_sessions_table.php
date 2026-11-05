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
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('opened_at');
            $table->unsignedBigInteger('opening_cents')->default(0);
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('closed_at')->nullable();
            $table->unsignedBigInteger('expected_cents')->nullable();
            $table->unsignedBigInteger('counted_cents')->nullable();
            $table->bigInteger('difference_cents')->nullable();
            $table->string('status', 20)->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('opened_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_sessions');
    }
};
