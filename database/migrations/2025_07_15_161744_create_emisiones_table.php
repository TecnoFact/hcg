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
        Schema::create('emisiones', function (Blueprint $table) {
            $table->id();
            $table->string('serie')->nullable();
            $table->string('folio')->nullable();
            $table->string('fecha')->nullable();
            $table->string('forma_pago')->nullable();
            $table->string('metodo_pago')->nullable();
            $table->string('tipo_comprobante')->nullable();
            $table->string('lugar_expedicion')->nullable();
            $table->string('moneda')->nullable();
            $table->string('emisor_rfc')->nullable();
            $table->string('emisor_nombre')->nullable();
            $table->string('emisor_regimen_fiscal')->nullable();
            $table->string('receptor_rfc')->nullable();
            $table->string('receptor_nombre')->nullable();
            $table->string('receptor_domicilio')->nullable();
            $table->string('receptor_regimen_fiscal')->nullable();
            $table->string('receptor_uso_cfdi')->nullable();
            $table->decimal('sub_total', 10, 2)->nullable();
            $table->decimal('iva', 10, 2)->nullable();
            $table->decimal('total', 10, 2)->nullable();

            $table->string('estado')->default('activo');
            $table->integer('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emisiones');
    }
};
