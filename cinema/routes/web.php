<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Главная страница → редирект на фильмы
Route::get('/', function () {
    return redirect()->route('movies.index');
});

// Dashboard
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Mock GitHub OAuth (для демонстрации)
Route::get('/auth/github/mock', [App\Http\Controllers\MockGithubAuthController::class, 'mockGithubLogin'])->name('github.mock.login');
Route::post('/auth/github/mock/callback', [App\Http\Controllers\MockGithubAuthController::class, 'mockGithubCallback'])->name('github.mock.callback');

// Реальный GitHub OAuth
Route::get('/auth/github', [App\Http\Controllers\GithubAuthController::class, 'redirectToGithub'])->name('github.login');
Route::get('/auth/github/callback', [App\Http\Controllers\GithubAuthController::class, 'handleGithubCallback']);

// Защищённые роуты (требуют авторизации)
Route::middleware('auth')->group(function () {
    // Профиль (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    Route::get('/my-movies', [App\Http\Controllers\MyMoviesController::class, 'index'])->name('my-movies.index');
    Route::delete('/my-movies/{movie}', [App\Http\Controllers\MyMoviesController::class, 'destroy'])->name('my-movies.destroy');

    Route::get('/my-bookings', [App\Http\Controllers\MyBookingsController::class, 'index'])->name('my-bookings.index');
    Route::get('/my-bookings/{booking}', [App\Http\Controllers\MyBookingsController::class, 'show'])->name('my-bookings.show');

    Route::post('/bookings', [App\Http\Controllers\BookingController::class, 'store'])->name('bookings.store');

    // CRUD фильмов
    Route::resource('movies', App\Http\Controllers\MovieController::class);
    
    // JWT endpoint для React
    Route::get('/api/me', function () {
        $user = Auth::user();
        
        $secretKey = 'cinema-booking-secret-key-change-in-production';
        
        $header = rtrim(strtr(base64_encode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT'
        ])), '+/', '-_'), '=');
        
        $payload = rtrim(strtr(base64_encode(json_encode([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'exp' => time() + 3600 // 1 час
        ])), '+/', '-_'), '=');
        
        $signature = rtrim(strtr(base64_encode(
            hash_hmac('sha256', "$header.$payload", $secretKey, true)
        ), '+/', '-_'), '=');
        
        $jwt = "$header.$payload.$signature";
        
        return response()->json([
            'token' => $jwt,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    })->name('api.me');
});

require __DIR__.'/auth.php';