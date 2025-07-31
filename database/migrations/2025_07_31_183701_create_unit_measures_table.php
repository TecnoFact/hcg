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
        Schema::create('unit_measures', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->unsignedInteger('created_uid')->nullable();
            $table->unsignedInteger('updated_uid')->nullable();

            //Campos
            $table->string('name');
            $table->string('code',4);
            $table->integer('decimal_place')->default(0);
            $table->integer('sort_order')->default(0);
            $table->boolean('status')->default(TRUE);

            //Index
            $table->index('id','id','BTREE');
            $table->unique('code','code','BTREE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_measures');
    }
};
