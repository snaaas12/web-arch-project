<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Session;
use App\Services\FastApiBookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BookingController extends Controller
{
    private FastApiBookingService $fastApiService;

    public function __construct(FastApiBookingService $fastApiService)
    {
        $this->fastApiService = $fastApiService;
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

        // 1. Пытаемся заблокировать места через FastAPI
        $lockResult = $this->fastApiService->lockSeats(
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

        // 3. Генерируем QR-код
        $qrCodeData = json_encode([
            'booking_id' => $booking->id,
            'session_id' => $booking->session_id,
            'seats' => $booking->seats,
            'user_id' => $booking->user_id,
        ]);
        
        $qrCodePath = storage_path("app/public/qr_codes/booking_{$booking->id}.png");
        \QrCode::format('png')->size(300)->generate($qrCodeData, $qrCodePath);
        
        $booking->update(['qr_code' => "qr_codes/booking_{$booking->id}.png"]);

        return response()->json([
            'success' => true,
            'message' => 'Бронирование успешно создано',
            'booking' => [
                'id' => $booking->id,
                'session_id' => $booking->session_id,
                'seats' => $booking->seats,
                'total_price' => $booking->total_price,
                'qr_code_url' => url("storage/{$booking->qr_code}"),
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