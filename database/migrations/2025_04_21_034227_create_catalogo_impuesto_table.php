<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_impuesto', function (Blueprint $table) {
$table->string('clave')->primary();
                $table->string('descripcion');
                $table->boolean('retencion')->nullable();
                $table->boolean('traslado')->nullable();
                $table->string('ambito')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_impuesto');
    }
};
