<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

use Illuminate\Support\Facades\Storage;


class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function (){
            Storage::disk('local')->put('text.txt', 'test2');
        })->everyMinute();

        $schedule->call(function (){
            $files = Storage::disk('local')->allFiles('providers');
            foreach ($files as $file){
                $nameArray = explode('/', $file);
                $country = substr($nameArray[1], 0, -4);
                $url = "https://api.themoviedb.org/3/watch/providers/movie?api_key=4d8868b4c38c4a941f15586d824cb806&language=en-US&watch_region=".$country;
                $json = file_get_contents($url);
                Storage::disk('local')->put($file, $json);
            }
        })->weekly();

        $schedule->call(function (){
            $url = 'https://api.themoviedb.org/3/genre/movie/list?api_key='.config('api.TMDB');
            $json = file_get_contents($url);
            Storage::disk('local')->put('genres.txt', $json);

        })->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
