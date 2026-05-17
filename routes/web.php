<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SendEmailController;
use App\Http\Controllers\AjaxController;
use App\Http\Controllers\CriteriaController;
use App\Http\Controllers\RandomMovieController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\RoulettesController;

Route::get('/',  [HomeController::class, 'index']);
Route::post('/', [SendEmailController::class, 'send']);

Route::get('/userinput', [AjaxController::class, 'index']);
Route::get('/criteria', [CriteriaController::class, 'index']);

Route::match(['get', 'post'], '/movie',    [RandomMovieController::class, 'show']);
Route::match(['get', 'post'], '/multiple', [RandomMovieController::class, 'multiple']);
Route::get('/movie/{id}', [MovieController::class, 'show'])->name('movie');

Route::get('/roulettes',                      [RoulettesController::class, 'show']);
Route::get('/roulettes/netflix/horror',       [RoulettesController::class, 'netflixHorror']);
Route::get('/roulettes/netflix/doc',          [RoulettesController::class, 'netflixDoc']);
Route::get('/roulettes/netflix/animovies',    [RoulettesController::class, 'netflixAnimeMovies']);

// Obfuscated utility routes (cache/config clear, scheduler trigger)
Route::get('/fdsdfsds', function () {
    Artisan::call('config:clear');
    Artisan::call('view:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
});

Route::get('/asdsadasdasdsadsaghfgh', function () {
    Artisan::call('schedule:run');
});
