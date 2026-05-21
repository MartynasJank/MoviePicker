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
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WatchlistController;
use App\Http\Controllers\TmdbProxyController;
use App\Http\Controllers\UserRouletteController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminRouletteController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\RowOrderController;

Route::get('/',  HomeController::class);
Route::post('/', ContactController::class);

// Auth
Route::get('/auth/google',          [AuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'callback']);
Route::post('/logout',              [AuthController::class, 'logout'])->name('logout');

// Watchlist (auth required)
Route::middleware('auth')->group(function () {
    Route::get('/watchlist',                    [WatchlistController::class, 'index'])->name('watchlist');
    Route::get('/watchlist/roll',               [WatchlistController::class, 'roll'])->name('watchlist.roll');
    Route::post('/watchlist/toggle',            [WatchlistController::class, 'toggle'])->name('watchlist.toggle');
    Route::delete('/watchlist/{tmdbId}',        [WatchlistController::class, 'remove'])->name('watchlist.remove');
    Route::patch('/watchlist/{tmdbId}/status',  [WatchlistController::class, 'setStatus'])->name('watchlist.status');
});

Route::get('/userinput', UserInputController::class);

Route::prefix('tmdb')->group(function () {
    Route::get('/search/movies', [TmdbProxyController::class, 'searchMovies']);
    Route::get('/search/people', [TmdbProxyController::class, 'searchPeople']);
    Route::get('/people/{id}',   [TmdbProxyController::class, 'person']);
});
Route::get('/criteria',  CriteriaController::class);

Route::match(['get', 'post'], '/movie',    [MoviePickController::class, 'single']);
Route::match(['get', 'post'], '/multiple', [MoviePickController::class, 'batch']);
Route::get('/movie/{id}', MovieController::class)->name('movie');

// My Roulettes (auth required — must be before /roulettes/{slug} wildcard)
Route::middleware('auth')->prefix('my-roulettes')->name('my-roulettes.')->group(function () {
    Route::get('/',                              [UserRouletteController::class, 'index'])->name('index');
    Route::get('/manage',                        [UserRouletteController::class, 'manage'])->name('manage');
    Route::get('/manage/create',                 [UserRouletteController::class, 'create'])->name('create');
    Route::post('/manage',                       [UserRouletteController::class, 'store'])->name('store');
    Route::get('/manage/{roulette}/edit',        [UserRouletteController::class, 'edit'])->name('edit');
    Route::put('/manage/{roulette}',             [UserRouletteController::class, 'update'])->name('update');
    Route::delete('/manage/{roulette}',          [UserRouletteController::class, 'destroy'])->name('destroy');
    Route::patch('/manage/{roulette}/toggle',    [UserRouletteController::class, 'togglePublic'])->name('toggle');
    Route::post('/manage/rows/reorder',          [UserRouletteController::class, 'reorderRows'])->name('rows.reorder');
});

// Admin (must be before /roulettes/{slug} wildcard)
Route::prefix('admin')->middleware(['auth', 'admin'])->name('admin.')->group(function () {
    Route::get('/',                             [AdminController::class, 'index'])->name('dashboard');
    Route::post('roulettes/reorder',            [AdminRouletteController::class, 'reorder'])->name('roulettes.reorder');
    Route::patch('roulettes/{roulette}/toggle',  [AdminRouletteController::class, 'togglePublic'])->name('roulettes.toggle');
    Route::patch('roulettes/{roulette}/system',  [AdminRouletteController::class, 'toggleSystem'])->name('roulettes.system');
    Route::resource('roulettes', AdminRouletteController::class)->except(['show']);
    Route::get('rows',                                          [RowOrderController::class, 'index'])->name('rows.index');
    Route::post('rows/reorder',                               [RowOrderController::class, 'reorder'])->name('rows.reorder');
    Route::get('users',                                        [AdminUserController::class, 'index'])->name('users.index');
    Route::get('users/{user}',                                 [AdminUserController::class, 'show'])->name('users.show');
    Route::delete('users/{user}/roulettes/{roulette}',         [AdminUserController::class, 'destroyRoulette'])->name('users.roulettes.destroy');
});

Route::get('/roulettes',        [RouletteController::class, 'index']);
Route::get('/roulettes/{slug}', [RouletteController::class, 'pick']);

// Legacy roulette URLs → redirect to new slugs
Route::get('/roulettes/netflix/horror',    fn() => redirect('/roulettes/netflix-horror', 301));
Route::get('/roulettes/netflix/doc',       fn() => redirect('/roulettes/netflix-docs', 301));
Route::get('/roulettes/netflix/animovies', fn() => redirect('/roulettes/netflix-anime', 301));

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
