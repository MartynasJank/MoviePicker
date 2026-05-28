<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use App\Services\MovieService;
use App\Http\Requests\CriteriaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class MoviePickController extends PickController
{
    private const SESSION_KEY = 'userInput';
    private const DEFAULTS = [
        'with_original_language'   => 'en',
        'primary_release_date_gte' => 1990,
        'vote_average_gte'         => 7,
        'vote_count_gte'           => 100,
    ];

    public function single(CriteriaRequest $request, MovieService $movieService, TmdbClient $tmdb): RedirectResponse
    {
        if ($redirect = $this->handleSessionReset($request, '/movie', self::SESSION_KEY, self::DEFAULTS)) {
            return $redirect;
        }

        $country  = $movieService->getUserCountry();
        $criteria = $movieService->resolveSessionCriteria($this->submitted($request), $request->isMethod('post'));
        $criteria['page'] = $movieService->resolvePage($tmdb, $criteria, $country);

        $results = $tmdb->discover($criteria, $country);

        if (empty($results['results'])) {
            $criteria['page'] = $movieService->randomPage(session('userInput.total_pages', 500), $criteria['page']);
            $results = $tmdb->discover($criteria, $country);
        }

        if (empty($results['results'])) {
            return redirect('/criteria');
        }

        session(['roll_source' => 'criteria']);
        return redirect()->route('movie', [$movieService->pickRandom($results['results'])['id']]);
    }

    public function batch(CriteriaRequest $request, MovieService $movieService, TmdbClient $tmdb): View|RedirectResponse
    {
        if ($redirect = $this->handleSessionReset($request, '/multiple', self::SESSION_KEY, self::DEFAULTS)) {
            return $redirect;
        }

        $country = $movieService->getUserCountry();
        $movies  = $this->restoreOrFetch($request, 'movie', function () use ($request, $movieService, $tmdb, $country) {
            $criteria          = $movieService->resolveSessionCriteria($this->submitted($request), $request->isMethod('post'));
            $criteria['page']  = $movieService->resolvePage($tmdb, $criteria, $country);
            $movies            = $tmdb->discover($criteria, $country);
            $movies['results'] = $movieService->pickBatch($movies['results']);
            return $movies;
        });

        $all_genres   = $movieService->genres($tmdb);
        $movie_genres = $movieService->genresMap($movies['results'], $all_genres);

        session(['batchUrl' => url('/multiple'), 'savedBatchUrl' => url('/multiple'), 'savedBatchResults' => $movies['results']]);

        return view('batch', [
            'movies'         => $movies,
            'user_input'     => session('userInput'),
            'all_genres'     => $all_genres,
            'movie_genres'   => $movie_genres,
            'providersArray' => $movieService->buildProvidersArray($tmdb),
            'tag'            => 'Movies picked for you',
            'savedIds'       => $this->savedWatchlistIds(),
        ]);
    }

    public function criteriaRoll(CriteriaRequest $request, MovieService $movieService, TmdbClient $tmdb): JsonResponse
    {
        $country   = $movieService->getUserCountry();
        $submitted = $this->submitted($request);
        $criteria  = $movieService->resolveSessionCriteria($submitted, $request->isMethod('post') && !empty($submitted));
        $criteria['page'] = $movieService->resolvePage($tmdb, $criteria, $country);

        $results = $tmdb->discover($criteria, $country);
        $picked  = $movieService->pickBatch($results['results'] ?? []);

        session([
            'lastBatchResults'  => $picked,
            'lastBatchType'     => 'movie',
            'batchUrl'          => url('/multiple'),
            'savedBatchUrl'     => url('/multiple'),
            'savedBatchResults' => $picked,
            'roll_source'       => 'criteria',
        ]);

        return response()->json($this->toRollCards($picked));
    }

    public function homepageRoll(MovieService $movieService, TmdbClient $tmdb): JsonResponse
    {
        session([self::SESSION_KEY => self::DEFAULTS]);

        $country = $movieService->getUserCountry();
        $filters = ['vote_count.gte' => 50, 'vote_average.gte' => 5];

        $totalPages = Cache::remember('homepage_movie_roll_pages_' . $country, now()->addDay(), function () use ($tmdb, $filters, $country) {
            $first = $tmdb->discover(array_merge($filters, ['page' => 1]), $country);
            return min($first['total_pages'] ?? 500, 500);
        });

        $results = $tmdb->discover(array_merge($filters, ['page' => rand(1, $totalPages)]), $country);

        $picked = $movieService->pickBatch($results['results'] ?? []);

        session([
            'lastBatchResults'  => $picked,
            'lastBatchType'     => 'movie',
            'batchUrl'          => url('/multiple'),
            'savedBatchUrl'     => url('/multiple'),
            'savedBatchResults' => $picked,
            'roll_source'       => 'other',
        ]);

        return response()->json($this->toRollCards($picked));
    }
}
