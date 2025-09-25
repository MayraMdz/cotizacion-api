<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 50);          // oficial, blue, mep, etc.
            $table->date('fecha');               // fecha de la cotización
            $table->decimal('compra', 10, 2)->nullable();
            $table->decimal('venta', 10, 2)->nullable();
            $table->json('payload')->nullable(); // respuesta completa opcional
            $table->timestamps();

            $table->unique(['tipo', 'fecha']);
            $table->index(['tipo', 'fecha']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('cotizaciones');
    }
};
