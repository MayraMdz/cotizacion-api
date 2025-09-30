<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');                       // YYYY-MM-DD
            $table->string('tipo')->default('oficial');  // oficial | blue | mep | ...
            $table->decimal('valor', 10, 2);             // valor de 1 USD en ARS
            $table->timestamps();

            $table->unique(['tipo', 'fecha']);
            $table->index(['tipo', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
