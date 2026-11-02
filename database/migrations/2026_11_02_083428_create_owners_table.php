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
        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('business_name', 150)->nullable();
            $table->string('document_type', 20)->default('ci');
            $table->string('document_number', 30)->nullable();
            $table->string('document_complement', 10)->nullable();
            $table->string('phone', 30);
            $table->string('phone_alt', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('portal_enabled')->default(false);
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('portal_terms_accepted_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['document_type', 'document_number', 'document_complement'],
                'owners_document_unique',
            );
            $table->index('phone');
            $table->index('email');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owners');
    }
};
