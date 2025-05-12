<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalogo_tipo_contrato', function (Blueprint $table) {
            $table->string('clave')->primary(); // c_TipoContrato
            $table->string('descripcion');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_tipo_contrato');
    }
};

