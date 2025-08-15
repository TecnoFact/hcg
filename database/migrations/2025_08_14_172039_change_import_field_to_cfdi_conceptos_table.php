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
            $table->decimal('valor_unitario', 14, 6)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cfdi_conceptos', function (Blueprint $table) {
            $table->decimal('valor_unitario', 14, 2)->change();
        });
    }
};
