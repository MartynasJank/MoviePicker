<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\UserInputController;
use App\Http\Controllers\CriteriaController;
use App\Http\Controllers\NoResultsController;
use App\Http\Controllers\MoviePickController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\RouletteController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WatchlistController;
use App\Http\Controllers\TmdbProxyController;
use App\Http\Controllers\UserRouletteController;
use App\Http\Controllers\TvCriteriaController;
use App\Http\Controllers\TvPickController;
use App\Http\Controllers\TvShowController;
use App\Http\Controllers\TvSeasonController;
use App\Http\Controllers\TvEpisodeController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\PersonRollController;
use App\Http\Controllers\BatchShareController;
use App\Http\Controllers\CollabBatchController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminRouletteController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\RowOrderController;

// ── Public pages ─────────────────────────────────────────────────────────────
Route::get('/', HomeController::class);
Route::post('/', ContactController::class);
Route::get('/privacy', fn() => view('privacy'))->name('privacy');
Route::get('/no-results', NoResultsController::class)->name('no-results');
Route::get('/criteria', CriteriaController::class);
Route::get('/tv/criteria', TvCriteriaController::class);

// ── Auth ──────────────────────────────────────────────────────────────────────
Route::get('/auth/google', [AuthController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'callback']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ── TMDB proxy (AJAX only) ────────────────────────────────────────────────────
Route::middleware('internal')->group(function () {
    Route::get('/userinput', UserInputController::class);
    Route::prefix('tmdb')->group(function () {
        Route::get('/search/all',    [TmdbProxyController::class, 'searchAll']);
        Route::get('/search/people', [TmdbProxyController::class, 'searchPeople']);
        Route::get('/people/{id}', [TmdbProxyController::class, 'person']);
    });
});

// ── Movie & TV picks (throttled) ──────────────────────────────────────────────
Route::middleware('throttle:60,1')->group(function () {
    Route::match(['get', 'post'], '/movie', [MoviePickController::class, 'single']);
    Route::match(['get', 'post'], '/multiple', [MoviePickController::class, 'batch']);
    Route::get('/movie/roll', [MoviePickController::class, 'homepageRoll']);
    Route::match(['get', 'post'], '/movie/roll/criteria', [MoviePickController::class, 'criteriaRoll']);

    Route::match(['get', 'post'], '/tv/pick', [TvPickController::class, 'single'])->name('tv.pick');
    Route::match(['get', 'post'], '/tv/multiple', [TvPickController::class, 'batch']);
    Route::get('/tv/roll', [TvPickController::class, 'homepageRoll']);
    Route::match(['get', 'post'], '/tv/roll/criteria', [TvPickController::class, 'criteriaRoll']);

    Route::get('/person/{id}/roll/movie', [PersonRollController::class, 'movie'])->name('person.roll.movie');
    Route::get('/person/{id}/roll/tv', [PersonRollController::class, 'tv'])->name('person.roll.tv');
    Route::middleware('internal')->group(function () {
        Route::get('/person/{id}/roll/movie/json', [PersonRollController::class, 'movieRollJson']);
        Route::get('/person/{id}/roll/tv/json', [PersonRollController::class, 'tvRollJson']);
    });
});

// ── Detail pages ──────────────────────────────────────────────────────────────
Route::get('/movie/{id}', MovieController::class)->name('movie');

Route::get('/tv/{id}', TvShowController::class)->name('tv.show');
Route::get('/tv/{id}/season/{season}', TvSeasonController::class)->name('tv.season');
Route::get('/tv/{id}/season/{season}/episode/{episode}', TvEpisodeController::class)->name('tv.episode');

Route::get('/person/roll/tv/next', [PersonRollController::class, 'nextTvRoll'])->name('person.roll.tv.next');
Route::get('/person/{id}', PersonController::class)->name('person');

// ── Roulettes ─────────────────────────────────────────────────────────────────
Route::get('/roulettes', [RouletteController::class, 'index']);
Route::get('/roulettes/{slug}/movies', [RouletteController::class, 'moviesJson']);
Route::get('/roulettes/{slug}', [RouletteController::class, 'show']);


// ── Batch & Collab ────────────────────────────────────────────────────────────
Route::get('/batch/share/{token}', [BatchShareController::class, 'show'])->name('batch.share');
Route::post('/batch/share', [BatchShareController::class, 'create'])->name('batch.share.create');

Route::post('/batch/collab', [CollabBatchController::class, 'create'])->name('batch.collab.create')->middleware('auth');
Route::get('/batch/collab/{token}', [CollabBatchController::class, 'show'])->name('batch.collab.show');
Route::prefix('batch/collab/{token}')->group(function () {
    Route::post('/join',         [CollabBatchController::class, 'join']);
    Route::post('/leave',        [CollabBatchController::class, 'leave']);
    Route::post('/heartbeat',    [CollabBatchController::class, 'heartbeat']);
    Route::post('/vote/{movieId}', [CollabBatchController::class, 'vote']);
    Route::post('/ready',        [CollabBatchController::class, 'toggleReady']);
    Route::post('/refresh',      [CollabBatchController::class, 'toggleRefreshVote']);
    Route::post('/remove-votes', [CollabBatchController::class, 'removeVotes']);
});

// ── Watchlist (auth required) ─────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/watchlist', [WatchlistController::class, 'index'])->name('watchlist');
    Route::get('/watchlist/roll', [WatchlistController::class, 'roll'])->name('watchlist.roll');
    Route::post('/watchlist/toggle', [WatchlistController::class, 'toggle'])->name('watchlist.toggle');
    Route::delete('/watchlist/{tmdbId}', [WatchlistController::class, 'remove'])->name('watchlist.remove');
    Route::patch('/watchlist/{tmdbId}/status', [WatchlistController::class, 'setStatus'])->name('watchlist.status');
});

// ── My Roulettes (auth required) ──────────────────────────────────────────────
Route::middleware('auth')->prefix('my-roulettes')->name('my-roulettes.')->group(function () {
    Route::get('/', [UserRouletteController::class, 'index'])->name('index');
    Route::get('/manage', [UserRouletteController::class, 'manage'])->name('manage');
    Route::get('/manage/create', [UserRouletteController::class, 'create'])->name('create');
    Route::post('/manage', [UserRouletteController::class, 'store'])->name('store');
    Route::get('/manage/{roulette}/edit', [UserRouletteController::class, 'edit'])->name('edit');
    Route::put('/manage/{roulette}', [UserRouletteController::class, 'update'])->name('update');
    Route::delete('/manage/{roulette}', [UserRouletteController::class, 'destroy'])->name('destroy');
    Route::patch('/manage/{roulette}/toggle', [UserRouletteController::class, 'togglePublic'])->name('toggle');
    Route::post('/manage/{roulette}/refresh-poster', [UserRouletteController::class, 'refreshPoster'])->name('refresh-poster');
    Route::post('/manage/rows/reorder', [UserRouletteController::class, 'reorderRows'])->name('rows.reorder');
    Route::post('/from-criteria', [UserRouletteController::class, 'fromCriteria'])->name('from-criteria');
});

// ── Admin (auth + admin role) ─────────────────────────────────────────────────
Route::prefix('admin')->middleware(['auth', 'admin'])->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
    Route::post('roulettes/reorder', [AdminRouletteController::class, 'reorder'])->name('roulettes.reorder');
    Route::post('roulettes/{roulette}/refresh-poster', [AdminRouletteController::class, 'refreshPoster'])->name('roulettes.refresh-poster');
    Route::patch('roulettes/{roulette}/toggle', [AdminRouletteController::class, 'togglePublic'])->name('roulettes.toggle');
    Route::resource('roulettes', AdminRouletteController::class)->except(['show']);
    Route::get('rows', [RowOrderController::class, 'index'])->name('rows.index');
    Route::post('rows/reorder', [RowOrderController::class, 'reorder'])->name('rows.reorder');
    Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::delete('users/{user}/roulettes/{roulette}', [AdminUserController::class, 'destroyRoulette'])->name('users.roulettes.destroy');
});

// ── Dev only ──────────────────────────────────────────────────────────────────
if (app()->environment('local')) {
    Route::get('/dev/login', function () {
        $email = config('api.admin_email') ?: 'dev@local.test';
        $user = \App\Models\User::firstOrCreate(
            ['email' => $email],
            ['name' => 'Dev User', 'provider' => 'local', 'provider_id' => 'local']
        );
        \Illuminate\Support\Facades\Auth::login($user, remember: true);
        return redirect('/');
    });
}
