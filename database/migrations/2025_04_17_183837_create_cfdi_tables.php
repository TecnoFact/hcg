<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCfdiTables extends Migration
{
    public function up(): void
    {
        Schema::create('cfdi_emisores', function (Blueprint $table) {
            $table->id();
            $table->string('rfc', 13);
            $table->string('nombre');
            $table->string('regimen_fiscal', 4);
            $table->timestamps();
        });

        Schema::create('cfdi_receptores', function (Blueprint $table) {
            $table->id();
            $table->string('rfc', 13);
            $table->string('nombre');
            $table->string('uso_cfdi', 3);
            $table->string('regimen_fiscal', 4);
            $table->string('domicilio_fiscal', 5);
            $table->timestamps();
        });

        Schema::create('cfdis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emisor_id')->constrained('cfdi_emisores');
            $table->foreignId('receptor_id')->constrained('cfdi_receptores');
            $table->string('serie')->nullable();
            $table->string('folio')->nullable();
            $table->dateTime('fecha');
            $table->decimal('subtotal', 14, 2);
            $table->decimal('descuento', 14, 2)->nullable();
            $table->decimal('total', 14, 2);
            $table->string('forma_pago', 2)->nullable();
            $table->string('metodo_pago', 3)->nullable();
            $table->string('moneda', 3)->default('MXN');
            $table->string('tipo_de_comprobante', 1);
            $table->string('exportacion', 3)->default('01');
            $table->string('lugar_expedicion', 5);
            $table->timestamps();
        });

        Schema::create('cfdi_conceptos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cfdi_id')->constrained('cfdis')->onDelete('cascade');
            $table->string('clave_prod_serv', 8);
            $table->string('no_identificacion')->nullable();
            $table->decimal('cantidad', 10, 2);
            $table->string('clave_unidad', 3);
            $table->string('unidad')->nullable();
            $table->string('descripcion');
            $table->decimal('valor_unitario', 14, 2);
            $table->decimal('importe', 14, 2);
            $table->decimal('descuento', 14, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('cfdi_impuestos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('concepto_id')->constrained('cfdi_conceptos')->onDelete('cascade');
            $table->enum('tipo', ['traslado', 'retencion']);
            $table->string('impuesto', 3);
            $table->string('tipo_factor');
            $table->decimal('tasa_cuota', 10, 6)->nullable();
            $table->decimal('importe', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cfdi_impuestos');
        Schema::dropIfExists('cfdi_conceptos');
        Schema::dropIfExists('cfdis');
        Schema::dropIfExists('cfdi_receptores');
        Schema::dropIfExists('cfdi_emisores');
    }
}
