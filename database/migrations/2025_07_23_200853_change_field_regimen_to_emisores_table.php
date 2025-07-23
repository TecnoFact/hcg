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
        Schema::table('emisores', function (Blueprint $table) {
            $table->string('tax_regimen_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emisores', function (Blueprint $table) {
            $table->integer('tax_regimen_id')->default(1);
        });
    }
};
