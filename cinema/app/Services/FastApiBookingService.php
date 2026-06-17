<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FastApiBookingService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.fastapi.url', 'http://fastapi:8000');
    }

    /**
     * Блокирует места через FastAPI
     */
    public function lockSeats(int $sessionId, array $seatIds, int $userId): array
    {
        try {
            $response = Http::timeout(10)->post("{$this->baseUrl}/api/v1/bookings/lock", [
                'session_id' => $sessionId,
                'seat_ids' => $seatIds,
                'user_id' => $userId,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            if ($response->status() === 409) {
                return [
                    'success' => false,
                    'error' => 'Одно или несколько мест уже забронированы',
                    'status' => 409,
                ];
            }

            return [
                'success' => false,
                'error' => 'Ошибка блокировки мест',
                'status' => $response->status(),
            ];

        } catch (\Exception $e) {
            Log::error('FastAPI lock error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Сервис бронирования недоступен',
                'status' => 503,
            ];
        }
    }

    /**
     * Проверяет статус конкретного места
     */
    public function checkSeat(int $sessionId, int $seatId): array
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/api/v1/bookings/check/{$sessionId}/{$seatId}");

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'is_locked' => false,
                'ttl_seconds' => 0,
            ];

        } catch (\Exception $e) {
            Log::error('FastAPI check error: ' . $e->getMessage());
            return [
                'is_locked' => false,
                'ttl_seconds' => 0,
            ];
        }
    }
}