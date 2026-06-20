<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Session;
use App\Services\RedisBookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    private RedisBookingService $redisService;

    public function __construct(RedisBookingService $redisService)
    {
        $this->redisService = $redisService;
    }

    /**
     * Создание бронирования
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|integer|exists:sessions,id',
            'seat_ids' => 'required|array|min:1|max:10',
            'seat_ids.*' => 'integer',
        ]);

        // Получаем текущего пользователя (для теста используем ID 1)
        $userId = Auth::id() ?? 1;

        // 1. Пытаемся заблокировать места через Redis (асинхронно через FastAPI worker)
        $lockResult = $this->redisService->lockSeats(
            $validated['session_id'],
            $validated['seat_ids'],
            $userId
        );

        if (!$lockResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $lockResult['error'],
            ], $lockResult['status'] ?? 400);
        }

        // 2. Создаем запись о бронировании в БД
        $session = Session::with('movie')->find($validated['session_id']);
        
        $booking = Booking::create([
            'user_id' => $userId,
            'session_id' => $validated['session_id'],
            'seats' => $validated['seat_ids'],
            'status' => 'confirmed',
            'total_price' => $session->base_price * count($validated['seat_ids']),
            'locked_until' => now()->addMinutes(10),
        ]);

        // 3. Генерируем QR-код через внешний API (НЕ требует расширений PHP)
        $qrCodeData = json_encode([
            'booking_id' => $booking->id,
            'session_id' => $booking->session_id,
            'seats' => $booking->seats,
            'user_id' => $booking->user_id,
        ]);

        $qrCodePath = storage_path("app/public/qr_codes/booking_{$booking->id}.png");
        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qrCodeData);

        // Скачиваем QR-код и сохраняем локально
        $qrContent = @file_get_contents($qrCodeUrl);
        if ($qrContent !== false) {
            file_put_contents($qrCodePath, $qrContent);
            $booking->update(['qr_code' => "qr_codes/booking_{$booking->id}.png"]);
            $qrCodeUrlFinal = url("storage/{$booking->qr_code}");
        } else {
            Log::warning('Не удалось сгенерировать QR-код для бронирования ' . $booking->id);
            $qrCodeUrlFinal = null;
        }

        return response()->json([
            'success' => true,
            'message' => 'Бронирование успешно создано',
            'booking' => [
                'id' => $booking->id,
                'session_id' => $booking->session_id,
                'seats' => $booking->seats,
                'total_price' => $booking->total_price,
                'qr_code_url' => $qrCodeUrlFinal,
            ],
        ], 201);
    }

    /**
     * Получение информации о бронировании
     */
    public function show(Booking $booking)
    {
        $booking->load(['session.movie', 'session.hall', 'user']);

        return response()->json([
            'success' => true,
            'booking' => $booking,
        ]);
    }
}