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
            $table->string('total')->nullable()->change();
            $table->string('subtotal')->nullable()->change();
            $table->string('descuento')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cfdis', function (Blueprint $table) {
            $table->dropColumn('total');
            $table->dropColumn('subtotal');
            $table->dropColumn('descuento');
        });
    }
};
