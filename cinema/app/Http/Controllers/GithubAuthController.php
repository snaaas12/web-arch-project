<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GithubAuthController extends Controller
{
    /**
     * Перенаправление на GitHub для авторизации
     */
    public function redirectToGithub()
    {
        return Socialite::driver('github')->redirect();
    }

    /**
     * Обработка callback от GitHub
     */
    public function handleGithubCallback()
    {
        try {
            $githubUser = Socialite::driver('github')->user();
        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Не удалось авторизоваться через GitHub');
        }

        // Ищем пользователя по github_id или email
        $user = User::where('github_id', $githubUser->getId())
            ->orWhere('email', $githubUser->getEmail())
            ->first();

        if ($user) {
            // Если пользователь существует, обновляем GitHub данные
            $user->update([
                'github_id' => $githubUser->getId(),
                'github_token' => $githubUser->token,
                'avatar' => $githubUser->getAvatar(),
            ]);
        } else {
            // Создаём нового пользователя
            $user = User::create([
                'name' => $githubUser->getName() ?? $githubUser->getNickname(),
                'email' => $githubUser->getEmail(),
                'github_id' => $githubUser->getId(),
                'github_token' => $githubUser->token,
                'avatar' => $githubUser->getAvatar(),
                'password' => bcrypt(Str::random(16)), // Случайный пароль
            ]);
        }

        // Авторизуем пользователя
        Auth::login($user, true);

        return redirect('/dashboard');
    }
}