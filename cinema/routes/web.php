<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('movies.index');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/auth/github/mock', [App\Http\Controllers\MockGithubAuthController::class, 'mockGithubLogin'])->name('github.mock.login');
Route::post('/auth/github/mock/callback', [App\Http\Controllers\MockGithubAuthController::class, 'mockGithubCallback'])->name('github.mock.callback');

Route::get('/auth/github', [App\Http\Controllers\GithubAuthController::class, 'redirectToGithub'])->name('github.login');
Route::get('/auth/github/callback', [App\Http\Controllers\GithubAuthController::class, 'handleGithubCallback']);

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::resource('movies', App\Http\Controllers\MovieController::class);
    Route::get('/api/me', function () {
    $user = Auth::user();
    
    // Общий секрет (должен совпадать с FastAPI)
    $secretKey = 'cinema-booking-secret-key-change-in-production';
    
    // Генерируем JWT вручную (как в методичке практики 11)
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
