<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_tasa_o_cuota', function (Blueprint $table) {
$table->string('rango')->primary(); // puede ser un valor fijo o un rango
                $table->decimal('valor_minimo', 20, 6)->nullable();
                $table->decimal('valor_maximo', 20, 6)->nullable();
                $table->boolean('traslado')->nullable();
                $table->boolean('retencion')->nullable();
                $table->string('tipo_factor')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_tasa_o_cuota');
    }
};
