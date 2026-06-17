<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained()->onDelete('cascade');
            $table->foreignId('hall_id')->constrained()->onDelete('cascade');
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable(); // вычисляется автоматически
            $table->decimal('base_price', 10, 2)->default(350.00); // базовая цена
            $table->enum('format', ['2D', '3D', 'IMAX', '4DX'])->default('2D');
            $table->timestamps();
            
            // Индекс для быстрого поиска сеансов по времени
            $table->index('start_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};