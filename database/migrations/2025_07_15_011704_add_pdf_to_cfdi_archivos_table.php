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
            $table->string('pdf_path')->nullable()->after('status_upload');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cfdi_archivos', function (Blueprint $table) {
            $table->dropColumn('pdf_path');
        });
    }
};
