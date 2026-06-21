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

    $userId = Auth::id();

    // 1. Проверяем, что места заблокированы текущим пользователем в Redis
    $allLockedByMe = true;
    foreach ($validated['seat_ids'] as $seatId) {
        $lockKey = "lock:session:{$validated['session_id']}:seat:{$seatId}";
        $lockedBy = \Illuminate\Support\Facades\Redis::get($lockKey);
        
        if ($lockedBy != $userId) {
            $allLockedByMe = false;
            break;
        }
    }

    if (!$allLockedByMe) {
        return response()->json([
            'success' => false,
            'message' => 'Одно или несколько мест не заблокированы вами',
        ], 409);
    }

    // 2. Проверяем, нет ли уже подтверждённых бронирований
    $existingBookings = Booking::where('session_id', $validated['session_id'])
        ->whereIn('status', ['confirmed', 'pending'])
        ->get();

    $conflictingSeats = [];
    foreach ($existingBookings as $booking) {
        $intersection = array_intersect($booking->seats, $validated['seat_ids']);
        if (!empty($intersection)) {
            $conflictingSeats = array_merge($conflictingSeats, $intersection);
        }
    }

    if (!empty($conflictingSeats)) {
        return response()->json([
            'success' => false,
            'message' => 'Некоторые места уже забронированы: ' . implode(', ', $conflictingSeats),
        ], 409);
    }

    // 3. Создаём бронирование
    $session = Session::with('movie')->find($validated['session_id']);
    
    $booking = Booking::create([
        'user_id' => $userId,
        'session_id' => $validated['session_id'],
        'seats' => $validated['seat_ids'],
        'status' => 'confirmed',
        'total_price' => $session->base_price * count($validated['seat_ids']),
        'locked_until' => now()->addMinutes(10),
    ]);

    // 4. Генерируем QR-код
    $qrCodeData = json_encode([
        'booking_id' => $booking->id,
        'session_id' => $booking->session_id,
        'seats' => $booking->seats,
        'user_id' => $booking->user_id,
    ]);

    $qrCodePath = storage_path("app/public/qr_codes/booking_{$booking->id}.png");
    $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qrCodeData);

    if (!file_exists(dirname($qrCodePath))) {
        mkdir(dirname($qrCodePath), 0755, true);
    }

    $qrContent = @file_get_contents($qrCodeUrl);
    if ($qrContent !== false) {
        file_put_contents($qrCodePath, $qrContent);
        $booking->update(['qr_code' => "qr_codes/booking_{$booking->id}.png"]);
    } else {
        \Log::warning('Не удалось сгенерировать QR-код для бронирования ' . $booking->id);
    }

    // 5. Очищаем блокировки из Redis
    foreach ($validated['seat_ids'] as $seatId) {
        $lockKey = "lock:session:{$validated['session_id']}:seat:{$seatId}";
        \Illuminate\Support\Facades\Redis::del($lockKey);
    }

    return response()->json([
        'success' => true,
        'message' => 'Бронирование успешно создано',
        'booking' => [
            'id' => $booking->id,
            'session_id' => $booking->session_id,
            'seats' => $booking->seats,
            'total_price' => $booking->total_price,
            'qr_code_url' => $booking->qr_code ? url("storage/{$booking->qr_code}") : null,
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