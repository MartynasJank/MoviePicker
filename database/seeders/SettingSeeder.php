<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::set('roulette_row_order', [
            'By Decade',
            'Netflix',
            'Prime Video',
            'HBO',
            'Disney+',
            'Apple TV+',
            'World Cinema',
            'Anime',
            'Community',
            'By Genre',
        ]);
    }
}
