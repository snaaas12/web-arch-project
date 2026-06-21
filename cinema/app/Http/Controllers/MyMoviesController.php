<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MyMoviesController extends Controller
{
    public function index()
    {
        $movies = Movie::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('my-movies.index', compact('movies'));
    }

    public function destroy(Movie $movie)
    {
        // Проверяем, что пользователь является владельцем
        if ($movie->user_id !== Auth::id()) {
            abort(403, 'У вас нет прав на удаление этого фильма');
        }

        $movie->delete();

        return redirect()->route('my-movies.index')
            ->with('success', 'Фильм успешно удалён');
    }
}
