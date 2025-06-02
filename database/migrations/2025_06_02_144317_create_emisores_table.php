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
        Schema::create('emisores', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->unique('rfc');
            $table->string('reason_social')->nullable();
            $table->string('website')->nullable();
            $table->string('phone')->nullable();
            $table->string('street')->nullable();
            $table->string('number_exterior')->nullable();
            $table->string('number_interior')->nullable();
            $table->string('colony')->nullable();
            $table->string('postal_code')->nullable();
            $table->integer('city_id')->nullable();
            $table->integer('state_id')->nullable();
            $table->integer('country_id')->nullable();
            $table->integer('tax_regimen_id')->default(1);
            $table->string('email')->nullable();
            $table->string('file_certificate')->nullable();
            $table->string('file_key')->nullable();
            $table->string('password_key')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('logo')->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emisores');
    }
};
