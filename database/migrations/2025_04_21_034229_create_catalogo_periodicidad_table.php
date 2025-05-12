<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_periodicidad', function (Blueprint $table) {
$table->string('clave')->primary();
                $table->string('descripcion');
                $table->string('equivalencia')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_periodicidad');
    }
};
