<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('cfdi_archivos', function (Blueprint $table) {
            $table->string('uuid')->nullable()->after('ruta');
            $table->longText('sello')->nullable()->after('uuid');
            $table->timestamp('fecha_envio_sat')->nullable()->after('estatus');
            $table->text('respuesta_sat')->nullable()->after('fecha_envio_sat');
            $table->string('token_sat', 255)->nullable()->after('respuesta_sat');
            $table->boolean('intento_envio_sat')->default(false)->after('token_sat');
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cfdi_archivos', function (Blueprint $table) {
            //
        });
    }
};
