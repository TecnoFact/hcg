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
        Schema::create('catalogo_regimen_fiscal', function (Blueprint $table) {
            $table->string('clave')->primary(); // Ejemplo: 601
            $table->string('descripcion');
            $table->boolean('persona_fisica')->default(false);
            $table->boolean('persona_moral')->default(false);
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
        Schema::dropIfExists('catalogo_regimen_fiscal');
    }
    
};
