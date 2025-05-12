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
        Schema::create('catalogo_tipo_de_comprobante', function (Blueprint $table) {
            $table->string('clave')->primary();
            $table->string('descripcion');
            $table->decimal('valor_maximo', 30, 6)->nullable(); //  Precisión SAT
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->timestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalogo_tipo_de_comprobante');
    }
};
