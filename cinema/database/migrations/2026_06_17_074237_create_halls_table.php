<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('halls', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // например "Зал 1", "IMAX"
            $table->enum('type', ['standard', 'imax', 'vip', 'premium'])->default('standard');
            $table->integer('rows_count'); // количество рядов
            $table->integer('seats_per_row'); // количество мест в ряду
            $table->json('seats_schema')->nullable(); // JSON схема мест (для нестандартных залов)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('halls');
    }
};