<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
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
});

require __DIR__.'/auth.php';
