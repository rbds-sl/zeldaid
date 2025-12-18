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
        Schema::create('wallet_pass_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('device_library_identifier')->index(); // Identificador único del dispositivo
            $table->string('pass_type_identifier')->index(); // Tipo de pass
            $table->string('serial_number')->index(); // Número del pass
            $table->string('push_token')->nullable(); // Token para enviar notificaciones push
            $table->datetime('registered_at')->nullable(); // Fecha de registro
            $table->datetime('last_updated_at')->nullable(); // Última actualización del pass
            $table->unique(['device_library_identifier', 'pass_type_identifier', 'serial_number']); // Combinación única
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_pass_registrations');
    }
};
