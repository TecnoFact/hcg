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
        Schema::table('cfdi_archivos', function (Blueprint $table) {
            $table->string('serie')->nullable();
            $table->string('folio')->nullable();

            $table->string('forma_pago')->nullable();
            $table->string('metodo_pago')->nullable();

            $table->string('lugar_expedicion')->nullable();
            $table->string('moneda')->nullable();

            $table->string('emisor_nombre')->nullable();
            $table->string('emisor_regimen_fiscal')->nullable();

            $table->string('receptor_nombre')->nullable();
            $table->string('receptor_domicilio')->nullable();
            $table->string('receptor_regimen_fiscal')->nullable();
            $table->string('receptor_uso_cfdi')->nullable();
            $table->decimal('sub_total', 10, 2)->nullable();
            $table->decimal('iva', 10, 2)->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cfdi_archivos', function (Blueprint $table) {
            $table->dropColumn([
                'serie',
                'folio',
                'forma_pago',
                'metodo_pago',
                'tipo_comprobante',
                'lugar_expedicion',
                'moneda',
                'emisor_nombre',
                'emisor_regimen_fiscal',
                'receptor_nombre',
                'receptor_domicilio',
                'receptor_regimen_fiscal',
                'receptor_uso_cfdi',
                'sub_total',
                'iva'
            ]);
        });
    }
};
