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
        Schema::create('wallet_pass_logs', function (Blueprint $table) {
            $table->id();
            $table->string('device_library_identifier')->index(); // Identificador del dispositivo
            $table->text('message'); // Mensaje de error o log
            $table->string('log_level')->default('info'); // error, warning, info, debug
            $table->string('pass_type_identifier')->nullable()->index(); // Tipo de pass relacionado
            $table->string('serial_number')->nullable()->index(); // Número del pass relacionado
            $table->json('context')->nullable(); // Contexto adicional del error
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_pass_logs');
    }
};
