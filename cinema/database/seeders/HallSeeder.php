<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Hall;

class HallSeeder extends Seeder
{
    public function run(): void
    {
        $halls = [
            ['name' => 'Зал 1', 'type' => 'standard', 'rows_count' => 10, 'seats_per_row' => 10],
            ['name' => 'Зал 2', 'type' => 'standard', 'rows_count' => 8, 'seats_per_row' => 12],
            ['name' => 'IMAX', 'type' => 'imax', 'rows_count' => 15, 'seats_per_row' => 15],
            ['name' => 'VIP', 'type' => 'vip', 'rows_count' => 5, 'seats_per_row' => 8],
        ];

        foreach ($halls as $hall) {
            Hall::create($hall);
        }
    }
}