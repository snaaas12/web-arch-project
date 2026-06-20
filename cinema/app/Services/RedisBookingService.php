<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RedisBookingService
{
    /**
     * Блокирует места через Redis (асинхронно через FastAPI worker)
     */
    public function lockSeats(int $sessionId, array $seatIds, int $userId): array
    {
        $requestId = Str::uuid()->toString();
        $requestQueue = 'booking:lock:requests';
        $responseKey = "booking:lock:response:{$requestId}";
        
        try {
            // 1. Публикуем запрос в очередь
            $request = [
                'request_id' => $requestId,
                'session_id' => $sessionId,
                'seat_ids' => $seatIds,
                'user_id' => $userId,
                'timestamp' => now()->timestamp,
            ];
            
            Redis::rpush($requestQueue, json_encode($request));
            
            // 2. Ждём ответ (с таймаутом 5 секунд)
            $response = null;
            $timeout = 5; // секунд
            $startTime = time();
            
            while (time() - $startTime < $timeout) {
                $responseData = Redis::get($responseKey);
                if ($responseData) {
                    $response = json_decode($responseData, true);
                    Redis::del($responseKey); // Удаляем ответ после получения
                    break;
                }
                usleep(100000); // 100ms пауза
            }
            
            // 3. Если ответ не получен (таймаут)
            if (!$response) {
                return [
                    'success' => false,
                    'error' => 'Таймаут ожидания ответа от сервиса бронирования',
                    'status' => 504,
                ];
            }
            
            // 4. Обрабатываем ответ
            if ($response['status'] === 'success') {
                return [
                    'success' => true,
                    'data' => $response,
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['message'] ?? 'Ошибка блокировки мест',
                    'status' => $response['status_code'] ?? 400,
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Redis booking error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Сервис бронирования недоступен',
                'status' => 503,
            ];
        }
    }
}