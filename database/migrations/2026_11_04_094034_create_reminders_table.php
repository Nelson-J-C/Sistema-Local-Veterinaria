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
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->morphs('reminderable');
            $table->foreignId('owner_id')->nullable()->constrained('owners')->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30);
            $table->dateTime('due_at');
            $table->string('channel', 20)->default('panel');
            $table->string('status', 20)->default('pending');
            $table->dateTime('sent_at')->nullable();
            $table->text('error')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['reminderable_type', 'reminderable_id', 'type', 'due_at', 'channel'], 'reminders_unique_due');
            $table->index(['status', 'due_at']);
            $table->index('patient_id');
            $table->index('owner_id');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
