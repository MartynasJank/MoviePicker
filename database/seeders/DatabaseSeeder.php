<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(RouletteSeeder::class);
        $this->call(TvRouletteSeeder::class);
        $this->call(SettingSeeder::class);
    }
}
