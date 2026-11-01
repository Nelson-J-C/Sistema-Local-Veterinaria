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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('email');
            $table->boolean('active')->default(true)->after('password');
            $table->string('license_number', 50)->nullable()->unique()->after('active');
            $table->string('specialty')->nullable()->after('license_number');
            $table->string('color', 7)->nullable()->after('specialty');
            $table->unsignedSmallInteger('default_appointment_duration')->default(30)->after('color');
            $table->text('app_authentication_secret')->nullable()->after('default_appointment_duration');
            $table->text('app_authentication_recovery_codes')->nullable()->after('app_authentication_secret');
            $table->softDeletes();

            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['active']);
            $table->dropUnique(['license_number']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'phone',
                'active',
                'license_number',
                'specialty',
                'color',
                'default_appointment_duration',
                'app_authentication_secret',
                'app_authentication_recovery_codes',
            ]);
        });
    }
};
