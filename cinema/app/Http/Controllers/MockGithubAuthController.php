<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MockGithubAuthController extends Controller
{
    /**
     * Имитация страницы входа GitHub
     */
    public function mockGithubLogin()
    {
        return view('auth.mock-github-login');
    }

    /**
     * Имитация callback от GitHub
     */
    public function mockGithubCallback()
    {
        // Имитируем данные от GitHub
        $mockGithubUser = (object) [
            'id' => 'mock-github-' . rand(1000, 9999),
            'name' => 'Mock GitHub User',
            'nickname' => 'mockuser',
            'email' => 'mock@github.example.com',
            'avatar' => 'https://github.githubassets.com/images/modules/logos_page/GitHub-Mark.png',
            'token' => 'mock-token-' . Str::random(20),
        ];

        // Ищем или создаём пользователя
        $user = User::where('email', $mockGithubUser->email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $mockGithubUser->name,
                'email' => $mockGithubUser->email,
                'github_id' => $mockGithubUser->id,
                'github_token' => $mockGithubUser->token,
                'avatar' => $mockGithubUser->avatar,
                'password' => bcrypt(Str::random(16)),
            ]);
        } else {
            $user->update([
                'github_id' => $mockGithubUser->id,
                'github_token' => $mockGithubUser->token,
                'avatar' => $mockGithubUser->avatar,
            ]);
        }

        Auth::login($user, true);

        return redirect('/dashboard')->with('success', 'Вы успешно вошли через GitHub (mock)');
    }
}