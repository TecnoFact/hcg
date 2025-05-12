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
        Schema::create('catalogo_uso_cfdi', function (Blueprint $table) {
            $table->string('clave')->primary(); // c_UsoCFDI
            $table->string('descripcion');
            $table->string('tipo_persona'); // F, M, FM, etc.
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->string('regimenes_fiscales')->nullable(); // lista separada por |
            $table->timestamps();
        });
    }
      

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalogo_uso_cfdi');
    }
    
};
