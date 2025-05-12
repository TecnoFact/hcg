<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_tipo_factor', function (Blueprint $table) {
            $table->string('clave')->primary(); // c_TipoFactor
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_tipo_factor');
    }
};
