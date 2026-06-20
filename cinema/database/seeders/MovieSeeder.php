<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Movie;
use App\Models\User;

class MovieSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        $movies = [
            [
                'user_id' => $user?->id ?? 1,
                'title' => 'Начало',
                'description' => 'Кобб — опытный сновидец, способный проникать в сны других людей.',
                'duration' => 148,
                'genre' => 'Фантастика',
                'rating' => 8.8,
                'poster_url' => 'https://picsum.photos/seed/inception/500/750',
                'age_restriction' => 12,
            ],
            [
                'user_id' => $user?->id ?? 1,
                'title' => 'Интерстеллар',
                'description' => 'Команда исследователей путешествует через червоточину в космосе.',
                'duration' => 169,
                'genre' => 'Фантастика',
                'rating' => 8.6,
                'poster_url' => 'https://picsum.photos/seed/interstellar/500/750',
                'age_restriction' => 12,
            ],
            [
                'user_id' => $user?->id ?? 1,
                'title' => 'Тёмный рыцарь',
                'description' => 'Бэтмен сталкивается с Джокером — преступным гением, погружающим Готэм в хаос.',
                'duration' => 152,
                'genre' => 'Боевик',
                'rating' => 9.0,
                'poster_url' => 'https://picsum.photos/seed/darkknight/500/750',
                'age_restriction' => 16,
            ],
            [
                'user_id' => $user?->id ?? 1,
                'title' => 'Побег из Шоушенка',
                'description' => 'Банкир, обвинённый в убийстве жены, попадает в тюрьму строгого режима.',
                'duration' => 142,
                'genre' => 'Драма',
                'rating' => 9.3,
                'poster_url' => 'https://picsum.photos/seed/shawshank/500/750',
                'age_restriction' => 16,
            ],
            [
                'user_id' => $user?->id ?? 1,
                'title' => 'Матрица',
                'description' => 'Хакер Нео узнаёт, что реальность — это симуляция, созданная машинами.',
                'duration' => 136,
                'genre' => 'Фантастика',
                'rating' => 8.7,
                'poster_url' => 'https://picsum.photos/seed/matrix/500/750',
                'age_restriction' => 16,
            ],
            [
                'user_id' => $user?->id ?? 1,
                'title' => 'Форрест Гамп',
                'description' => 'История человека с низким IQ, который стал свидетелем ключевых событий XX века.',
                'duration' => 142,
                'genre' => 'Драма',
                'rating' => 8.8,
                'poster_url' => 'https://picsum.photos/seed/forrest/500/750',
                'age_restriction' => 12,
            ],
        ];

        foreach ($movies as $movie) {
            Movie::create($movie);
        }
    }
}