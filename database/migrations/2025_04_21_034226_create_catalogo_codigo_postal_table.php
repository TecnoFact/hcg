<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_codigo_postal', function (Blueprint $table) {
            $table->string('clave')->primary(); // c_CodigoPostal
            $table->string('estado')->nullable();
            $table->string('municipio')->nullable();
            $table->string('localidad')->nullable();
            $table->boolean('estimulo_franja_fronteriza')->default(false)->nullable();
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->string('referencias_huso_horario')->nullable();
            $table->string('descripcion_huso_horario')->nullable();
            $table->string('mes_inicio_horario_verano')->nullable();
            $table->string('dia_inicio_horario_verano')->nullable();
            $table->string('diferencia_horaria_verano')->nullable();
            $table->string('mes_inicio_horario_invierno')->nullable();
            $table->string('dia_inicio_horario_invierno')->nullable();
            $table->string('diferencia_horaria_invierno')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_codigo_postal');
    }
};
