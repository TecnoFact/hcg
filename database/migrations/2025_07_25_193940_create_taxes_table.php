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
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique()->comment('Nombre del impuesto');
            $table->string('code')->unique()->comment('Código del impuesto');
            $table->decimal('rate', 5, 2)->comment('Tasa del impuesto');
            $table->boolean('is_active')->default(true)->comment('Estado del impuesto');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
