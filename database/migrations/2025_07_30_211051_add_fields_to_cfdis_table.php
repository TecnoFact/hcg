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
        Schema::table('cfdis', function (Blueprint $table) {
            $table->string('nombre_archivo')->nullable();
            $table->string('ruta')->nullable();
            $table->string('uuid')->nullable();
            $table->string('sello')->nullable();
            $table->string('estatus')->nullable();
            $table->dateTime('fecha_envio_sat')->nullable();
            $table->text('respuesta_sat')->nullable();
            $table->string('token_sat')->nullable();
            $table->integer('intento_envio_sat')->default(0);
            $table->string('status_upload')->nullable();
            $table->string('pdf_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cfdis', function (Blueprint $table) {
            $table->dropColumn([
                'nombre_archivo',
                'ruta',
                'uuid',
                'sello',
                'estatus',
                'fecha_envio_sat',
                'respuesta_sat',
                'token_sat',
                'intento_envio_sat',
                'status_upload',
                'pdf_path',
            ]);
        });
    }
};
