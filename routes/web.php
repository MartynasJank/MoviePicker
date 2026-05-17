<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\UserInputController;
use App\Http\Controllers\CriteriaController;
use App\Http\Controllers\MoviePickController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\RouletteController;

Route::get('/',  HomeController::class);
Route::post('/', ContactController::class);

Route::get('/userinput', UserInputController::class);
Route::get('/criteria',  CriteriaController::class);

Route::match(['get', 'post'], '/movie',    [MoviePickController::class, 'show']);
Route::match(['get', 'post'], '/multiple', [MoviePickController::class, 'multiple']);
Route::get('/movie/{id}', MovieController::class)->name('movie');

Route::get('/roulettes',                   [RouletteController::class, 'show']);
Route::get('/roulettes/netflix/horror',    [RouletteController::class, 'netflixHorror']);
Route::get('/roulettes/netflix/doc',       [RouletteController::class, 'netflixDoc']);
Route::get('/roulettes/netflix/animovies', [RouletteController::class, 'netflixAnimeMovies']);

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
