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
        Schema::create('objeto_imp', function (Blueprint $table) {
            $table->id();
            $table->string('descripcion')->nullable()->comment('Descripción del objeto de impuesto');
            $table->string('clave')->unique()->comment('Clave del objeto de impuesto');
            $table->boolean('is_active')->default(true)->comment('Estado del objeto de impuesto');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('objeto_imp');
    }
};
