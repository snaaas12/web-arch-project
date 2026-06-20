<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            HallSeeder::class,
            MovieSeeder::class,
            SessionSeeder::class,
        ]);
    }
}