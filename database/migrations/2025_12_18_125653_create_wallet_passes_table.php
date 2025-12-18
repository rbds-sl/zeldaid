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
        Schema::create('wallet_passes', function (Blueprint $table) {
            $table->id();
            $table->string('pass_type_identifier')->index(); // pass.com.example.appname
            $table->string('serial_number')->index(); // Número único del pass
            $table->unique(['pass_type_identifier', 'serial_number']); // Combinación única
            $table->json('data')->nullable(); // Datos del pass (estructura JSON)
            $table->string('template_type')->default('boarding_pass'); // Tipo de pass
            $table->datetime('version_updated_at')->nullable(); // Última actualización
            $table->string('created_by')->nullable(); // Usuario/app que creó el pass
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_passes');
    }
};
