<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('session_id')->constrained()->onDelete('cascade');
            $table->json('seats'); // JSON массив: [{"row": 5, "seat": 12}, {"row": 5, "seat": 13}]
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'expired'])->default('pending');
            $table->decimal('total_price', 10, 2)->default(0.00);
            $table->string('qr_code')->nullable(); // путь к QR-коду или base64
            $table->timestamp('locked_until')->nullable(); // время истечения блокировки
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index(['session_id', 'status']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};