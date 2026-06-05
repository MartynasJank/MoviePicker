<?php

namespace App\Http\Controllers;

use App\Models\Roulette;
use App\Services\MovieService;
use App\Services\RouletteTagMapper;
use App\Services\TmdbClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SwipeController extends Controller
{
    private const DEFAULTS = [
        'with_original_language'   => 'en',
        'primary_release_date_gte' => 1990,
        'vote_average_gte'         => 6,
        'vote_count_gte'           => 50,
    ];

    public function index(Request $request, MovieService $movieService, TmdbClient $tmdb): View
    {
        $country    = $movieService->getUserCountry();
        $forceReset = $request->boolean('reset');
        $criteria   = $movieService->resolveSessionCriteria(self::DEFAULTS, $forceReset);
        $all_genres     = $movieService->genres($tmdb);
        $providersArray = $movieService->buildProvidersArray($tmdb);
        $user_input     = session('userInput', 'default');
        $page           = $movieService->resolvePage($tmdb, $criteria, $country);
        $results        = $tmdb->discover(array_merge($criteria, ['page' => $page]), $country);
        $movies         = $this->attachGenres($movieService->pickBatch($results['results'] ?? []), $all_genres, $page);
        $totalResults   = $results['total_results'] ?? 0;

        $watchlistIds = auth()->check()
            ? auth()->user()->watchlist()->pluck('tmdb_id')->all()
            : [];

        return view('swipe', [
            'movies'         => $movies,
            'initialPage'    => $page,
            'totalResults'   => $totalResults,
            'isLoggedIn'     => auth()->check(),
            'isAdmin'        => auth()->check() && auth()->user()->email === config('api.admin_email'),
            'watchlistIds'   => $watchlistIds,
            'all_genres'     => $all_genres,
            'providersArray' => $providersArray,
            'user_input'     => $user_input,
        ]);
    }

    public function fromRoulette(string $slug, MovieService $movieService): RedirectResponse
    {
        $roulette = Roulette::where('slug', $slug)
            ->where(fn($q) => $q->where('is_public', true)->orWhere('user_id', auth()->id()))
            ->firstOrFail();

        $mapper = new RouletteTagMapper();
        $base   = $roulette->media_type === 'tv'
            ? $mapper->toCriteriaTv($roulette->tags)
            : $mapper->toCriteriaMovie($roulette->tags);

        $sessionCriteria = [];
        foreach ($base as $key => $value) {
            $sessionCriteria[str_replace('.', '_', $key)] = $value;
        }
        session(['userInput' => $sessionCriteria]);

        return redirect()->route('swipe');
    }

    public function load(Request $request, MovieService $movieService, TmdbClient $tmdb): JsonResponse
    {
        $country = $movieService->getUserCountry();

        // Build criteria from the full criteria form fields
        $input = array_filter([
            'with_genres'                => $request->input('with_genres'),
            'without_genres'             => $request->input('without_genres'),
            'with_watch_providers'       => $request->input('with_watch_providers'),
            'primary_release_date_gte'   => $request->input('primary_release_date_gte'),
            'primary_release_date_lte'   => $request->input('primary_release_date_lte'),
            'vote_average_gte'           => $request->input('vote_average_gte'),
            'vote_average_lte'           => $request->input('vote_average_lte'),
            'vote_count_gte'             => $request->input('vote_count_gte'),
            'with_original_language'     => $request->input('with_original_language'),
            'with_origin_country'        => $request->input('with_origin_country'),
            'with_cast'                  => $request->input('with_cast'),
            'with_crew'                  => $request->input('with_crew'),
        ], fn($v) => !empty($v));

        session(['userInput' => $input]);

        $genres  = $movieService->genres($tmdb);
        $page    = $movieService->resolvePage($tmdb, $input, $country);
        $results = $tmdb->discover(array_merge($input, ['page' => $page]), $country);
        $movies  = $this->attachGenres($movieService->pickBatch($results['results'] ?? []), $genres, $page);

        return response()->json(['movies' => $movies, 'page' => $page, 'total_results' => $results['total_results'] ?? 0]);
    }

    public function next(Request $request, MovieService $movieService, TmdbClient $tmdb): JsonResponse
    {
        $country   = $movieService->getUserCountry();
        $criteria  = $movieService->resolveSessionCriteria([], false);
        $seenPages = array_map('intval', $request->input('seen_pages', []));
        $totalPages = session('userInput.total_pages', 500);
        $genres  = $movieService->genres($tmdb);
        $page    = $this->pickPage((int) $totalPages, $seenPages);
        $results = $tmdb->discover(array_merge($criteria, ['page' => $page]), $country);
        $movies  = $this->attachGenres($movieService->pickBatch($results['results'] ?? []), $genres, $page);

        return response()->json(['movies' => $movies, 'page' => $page]);
    }

    private function attachGenres(array $movies, array $allGenres, int $page = 0): array
    {
        $idToName = collect($allGenres)->pluck('name', 'id')->all();
        return array_map(function ($movie, $i) use ($idToName, $page) {
            $names = array_filter(array_map(fn($id) => $idToName[$id] ?? null, $movie['genre_ids'] ?? []));
            $movie['genres']    = implode(', ', $names);
            $movie['_page']     = $page;
            $movie['_pos']      = $i + 1;
            return $movie;
        }, $movies, array_keys($movies));
    }

    private function pickPage(int $total, array $exclude): int
    {
        $total = min(max($total, 1), 500);
        $pool  = array_values(array_diff(range(1, $total), $exclude));
        if (empty($pool)) $pool = range(1, $total);
        return $pool[array_rand($pool)];
    }
}
