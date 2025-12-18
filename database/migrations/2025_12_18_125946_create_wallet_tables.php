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
        // Tabla de passes
        Schema::create('wallet_passes', function (Blueprint $table) {
            $table->id();
            $table->string('pass_type_identifier')->index();
            $table->string('serial_number')->index();
            $table->unique(['pass_type_identifier', 'serial_number']);
            $table->json('data')->nullable();
            $table->string('template_type')->default('boarding_pass');
            $table->datetime('version_updated_at')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        // Tabla de registros de dispositivos
        Schema::create('wallet_pass_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('device_library_identifier')->index();
            $table->string('pass_type_identifier')->index();
            $table->string('serial_number')->index();
            $table->string('push_token')->nullable();
            $table->datetime('registered_at')->nullable();
            $table->datetime('last_updated_at')->nullable();
            $table->unique(['device_library_identifier', 'pass_type_identifier', 'serial_number']);
            $table->timestamps();
        });

        // Tabla de logs
        Schema::create('wallet_pass_logs', function (Blueprint $table) {
            $table->id();
            $table->string('device_library_identifier')->index();
            $table->text('message');
            $table->string('log_level')->default('info');
            $table->string('pass_type_identifier')->nullable()->index();
            $table->string('serial_number')->nullable()->index();
            $table->json('context')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_pass_logs');
        Schema::dropIfExists('wallet_pass_registrations');
        Schema::dropIfExists('wallet_passes');
    }
};
