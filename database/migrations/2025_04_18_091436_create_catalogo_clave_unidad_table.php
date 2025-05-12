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
        Schema::create('catalogo_clave_unidad', function (Blueprint $table) {
            $table->string('clave')->primary(); // c_ClaveUnidad
            $table->string('nombre');
            $table->text('descripcion');
            $table->text('nota')->nullable();
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->string('simbolo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalogo_clave_unidad');
    }
};
