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
            $table->string('path_xml')->nullable()->after('lugar_expedicion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cfdis', function (Blueprint $table) {
            $table->dropColumn('path_xml');
        });
    }
};
