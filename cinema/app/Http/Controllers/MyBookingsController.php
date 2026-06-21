<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MyBookingsController extends Controller
{
    public function index()
    {
        $bookings = Booking::where('user_id', Auth::id())
            ->with(['session.movie', 'session.hall'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('my-bookings.index', compact('bookings'));
    }

    public function show(Booking $booking)
    {
        // Проверяем, что пользователь является владельцем
        if ($booking->user_id !== Auth::id()) {
            abort(403, 'У вас нет прав на просмотр этого билета');
        }

        $booking->load(['session.movie', 'session.hall', 'user']);

        return view('my-bookings.show', compact('booking'));
    }
}
