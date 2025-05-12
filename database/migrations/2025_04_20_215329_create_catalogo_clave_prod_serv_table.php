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
        Schema::create('catalogo_clave_prod_serv', function (Blueprint $table) {
            $table->string('clave')->primary(); // c_ClaveProdServ
            $table->string('descripcion');
            $table->boolean('incluir_iva_trasladado')->default(false);
            $table->boolean('incluir_ieps_trasladado')->default(false);
            $table->string('complemento')->nullable();
            $table->date('vigencia_desde')->nullable(); // FechaInicioVigencia
            $table->date('vigencia_hasta')->nullable(); // FechaFinVigencia
            $table->string('estimulo_franja_fronteriza')->nullable();
            $table->text('palabras_similares')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalogo_clave_prod_serv');
    }
};
