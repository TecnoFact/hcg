<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_objeto_imp', function (Blueprint $table) {
$table->string('clave')->primary();
                $table->string('descripcion');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_objeto_imp');
    }
};
