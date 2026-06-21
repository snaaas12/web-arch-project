<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class MovieController extends Controller
{
    /**
     * Список всех фильмов
     */
    public function index()
    {
        $movies = Movie::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('movies.index', compact('movies'));
    }
    /**
     * Форма создания фильма
     */
    public function create()
    {
        return view('movies.create');
    }

    /**
     * Сохранение нового фильма
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'required|integer|min:1|max:600',
            'genre' => 'nullable|string|max:100',
            'rating' => 'nullable|numeric|min:0|max:10',
            'age_restriction' => 'nullable|integer|min:0|max:21',
        ]);

        $validated['user_id'] = Auth::id();
        $movie = Movie::create($validated);

        // Публикуем событие в Redis
        Redis::publish('cinema.movies', json_encode([
            'event' => 'movie.created',
            'movie_id' => $movie->id,
            'title' => $movie->title,
            'user_id' => Auth::id(),
            'timestamp' => now()->timestamp,
        ]));

        Log::info("Movie created: {$movie->id} - {$movie->title}");

        return redirect()->route('movies.show', $movie)
            ->with('success', 'Фильм успешно создан!');
    }

    /**
     * Просмотр одного фильма
     */
    public function show(Movie $movie)
    {
        $movie->load(['user', 'sessions.hall']);
        return view('movies.show', compact('movie'));
    }

    /**
     * Форма редактирования фильма
     */
    public function edit(Movie $movie)
    {
        // Проверка, что текущий пользователь — владелец
        if ($movie->user_id !== Auth::id()) {
            abort(403, 'Вы можете редактировать только свои фильмы');
        }

        return view('movies.edit', compact('movie'));
    }

    /**
     * Обновление фильма
     */
    public function update(Request $request, Movie $movie)
    {
        // Проверка владельца
        if ($movie->user_id !== Auth::id()) {
            abort(403, 'Вы можете редактировать только свои фильмы');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'required|integer|min:1|max:600',
            'genre' => 'nullable|string|max:100',
            'rating' => 'nullable|numeric|min:0|max:10',
            'poster_url' => 'nullable|url|max:500',
            'age_restriction' => 'nullable|integer|min:0|max:21',
        ]);

        $movie->update($validated);

        // Публикуем событие в Redis
        Redis::publish('cinema.movies', json_encode([
            'event' => 'movie.updated',
            'movie_id' => $movie->id,
            'title' => $movie->title,
            'user_id' => Auth::id(),
            'timestamp' => now()->timestamp,
        ]));

        Log::info("Movie updated: {$movie->id} - {$movie->title}");

        return redirect()->route('movies.show', $movie)
            ->with('success', 'Фильм успешно обновлён!');
    }

    /**
     * Удаление фильма
     */
    public function destroy(Movie $movie)
    {
        // Проверка владельца
        if ($movie->user_id !== Auth::id()) {
            abort(403, 'Вы можете удалять только свои фильмы');
        }

        $movieTitle = $movie->title;
        $movieId = $movie->id;
        $movie->delete();

        // Публикуем событие в Redis
        Redis::publish('cinema.movies', json_encode([
            'event' => 'movie.deleted',
            'movie_id' => $movieId,
            'title' => $movieTitle,
            'user_id' => Auth::id(),
            'timestamp' => now()->timestamp,
        ]));

        Log::info("Movie deleted: {$movieId} - {$movieTitle}");

        return redirect()->route('movies.index')
            ->with('success', 'Фильм успешно удалён!');
    }
}