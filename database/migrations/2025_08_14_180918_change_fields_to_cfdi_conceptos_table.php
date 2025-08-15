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
        Schema::table('cfdi_conceptos', function (Blueprint $table) {
            $table->string('valor_unitario')->nullable()->change();
            $table->string('importe')->nullable()->change();
            $table->string('descuento')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cfdi_conceptos', function (Blueprint $table) {
            $table->dropColumn('valor_unitario');
            $table->dropColumn('importe');
            $table->dropColumn('descuento');
        });
    }
};
