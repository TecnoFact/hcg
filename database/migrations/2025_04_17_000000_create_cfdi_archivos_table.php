<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cfdi_archivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nombre_archivo');
            $table->string('ruta');
            $table->string('rfc_emisor', 13)->nullable();
            $table->string('rfc_receptor', 13)->nullable();
            $table->decimal('total', 14, 2)->nullable();
            $table->dateTime('fecha')->nullable();
            $table->string('tipo_comprobante', 5)->nullable();
            $table->enum('estatus', ['pendiente', 'validado', 'timbrado', 'rechazado'])->default('pendiente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cfdi_archivos');
    }
};
