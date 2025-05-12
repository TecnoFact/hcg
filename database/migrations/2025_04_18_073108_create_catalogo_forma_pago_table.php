<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalogo_forma_pago', function (Blueprint $table) {
            $table->string('clave')->primary(); // c_FormaPago
            $table->string('descripcion');
            $table->boolean('bancarizado')->default(false);
            $table->boolean('requiere_numero_operacion')->default(false);
            $table->boolean('requiere_rfc_emisor_cuenta_ordenante')->default(false);
            $table->boolean('requiere_cuenta_ordenante')->default(false);
            $table->string('patron_cuenta_ordenante')->nullable();
            $table->boolean('requiere_rfc_emisor_cuenta_beneficiario')->default(false);
            $table->boolean('requiere_cuenta_beneficiario')->default(false);
            $table->string('patron_cuenta_beneficiario')->nullable();
            $table->boolean('requiere_tipo_cadena_pago')->default(false);
            $table->string('nombre_banco_extranjero')->nullable();
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_forma_pago');
    }
};
