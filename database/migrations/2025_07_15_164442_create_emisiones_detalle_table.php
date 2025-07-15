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
        Schema::create('emisiones_detalle', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('emision_id');
            $table->string('clave_prod_serv')->nullable();
            $table->string('numero_identificacion')->nullable();
            $table->integer('cantidad')->nullable();
            $table->string('clave_unidad')->nullable();
            $table->string('unidad')->nullable();
            $table->decimal('valor_unitario', 10, 2)->nullable();
            $table->text('descripcion')->nullable();
            $table->string('tipo_impuesto')->nullable();
            $table->decimal('importe', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emisiones_detalle');
    }
};
