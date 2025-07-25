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
            $table->integer('tax_id')->nullable()->after('tipo_impuesto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cfdi_conceptos', function (Blueprint $table) {
            $table->dropColumn('tax_id');
        });
    }
};
