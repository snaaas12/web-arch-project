<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Session;
use App\Models\Movie;
use App\Models\Hall;
use Carbon\Carbon;

class SessionSeeder extends Seeder
{
    public function run(): void
    {
        $movies = Movie::all();
        $halls = Hall::all();

        if ($movies->isEmpty() || $halls->isEmpty()) {
            $this->command->warn('Сначала запустите MovieSeeder и HallSeeder');
            return;
        }

        $formats = ['2D', '3D', 'IMAX'];
        
        // Создаём сеансы на ближайшие 7 дней
        for ($day = 0; $day < 7; $day++) {
            $date = Carbon::now()->addDays($day);
            
            foreach ($movies as $movie) {
                // 2-3 сеанса в день для каждого фильма
                $times = ['14:00', '17:00', '20:00'];
                
                foreach ($times as $time) {
                    $startTime = $date->copy()->setTimeFromTimeString($time);
                    $endTime = $startTime->copy()->addMinutes($movie->duration);
                    
                    Session::create([
                        'movie_id' => $movie->id,
                        'hall_id' => $halls->random()->id,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'base_price' => rand(300, 500),
                        'format' => $formats[array_rand($formats)],
                    ]);
                }
            }
        }
    }
}