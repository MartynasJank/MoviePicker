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
use App\Http\Controllers\TmdbProxyController;

Route::get('/',  HomeController::class);
Route::post('/', ContactController::class);

Route::get('/userinput', UserInputController::class);

Route::prefix('tmdb')->group(function () {
    Route::get('/search/people', [TmdbProxyController::class, 'searchPeople']);
    Route::get('/people/{id}',   [TmdbProxyController::class, 'person']);
});
Route::get('/criteria',  CriteriaController::class);

Route::match(['get', 'post'], '/movie',    [MoviePickController::class, 'single']);
Route::match(['get', 'post'], '/multiple', [MoviePickController::class, 'batch']);
Route::get('/movie/{id}', MovieController::class)->name('movie');

Route::get('/roulettes',                [RouletteController::class, 'index']);
Route::get('/roulettes/netflix/{type}', [RouletteController::class, 'pick']);

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
