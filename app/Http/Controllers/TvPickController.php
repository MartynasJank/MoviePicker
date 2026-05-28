<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use App\Services\MovieService;
use App\Http\Requests\TvCriteriaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TvPickController extends PickController
{
    private const SESSION_KEY = 'tvInput';
    private const CLEAR_WITH  = ['tvPersonRollIds'];
    private const DEFAULTS    = [
        'with_original_language' => 'en',
        'first_air_date_gte'     => 1990,
        'vote_average_gte'       => 7,
        'vote_count_gte'         => 100,
    ];

    public function single(TvCriteriaRequest $request, MovieService $movieService, TmdbClient $tmdb): RedirectResponse
    {
        if ($redirect = $this->handleSessionReset($request, '/tv/pick', self::SESSION_KEY, self::DEFAULTS, self::CLEAR_WITH)) {
            return $redirect;
        }

        $country  = $movieService->getUserCountry();
        $criteria = $movieService->resolveSessionCriteria($this->submitted($request), $request->isMethod('post'), self::SESSION_KEY, self::CLEAR_WITH);
        $criteria['page'] = $movieService->resolvePage($tmdb, $criteria, $country, 'tv');

        $results = $tmdb->discoverTv($criteria, $country);

        if (empty($results['results'])) {
            $criteria['page'] = $movieService->randomPage(session('tvInput.total_pages', 500), $criteria['page']);
            $results = $tmdb->discoverTv($criteria, $country);
        }

        if (empty($results['results'])) {
            return redirect('/tv/criteria');
        }

        return redirect()->route('tv.show', [$movieService->pickRandom($results['results'])['id']]);
    }

    public function batch(TvCriteriaRequest $request, MovieService $movieService, TmdbClient $tmdb): View|RedirectResponse
    {
        if ($redirect = $this->handleSessionReset($request, '/tv/multiple', self::SESSION_KEY, self::DEFAULTS, self::CLEAR_WITH)) {
            return $redirect;
        }

        $country = $movieService->getUserCountry();
        $shows   = $this->restoreOrFetch($request, 'tv', function () use ($request, $movieService, $tmdb, $country) {
            $criteria         = $movieService->resolveSessionCriteria($this->submitted($request), $request->isMethod('post'), self::SESSION_KEY, self::CLEAR_WITH);
            $criteria['page'] = $movieService->resolvePage($tmdb, $criteria, $country, 'tv');
            $shows            = $tmdb->discoverTv($criteria, $country);
            $shows['results'] = $movieService->pickBatch($shows['results']);
            $shows['results'] = $movieService->normaliseShows($shows['results']);
            return $shows;
        });

        $all_genres   = $movieService->genres($tmdb, 'tv');
        $movie_genres = $movieService->genresMap($shows['results'], $all_genres);

        session(['batchUrl' => url('/tv/multiple'), 'savedBatchUrl' => url('/tv/multiple'), 'savedBatchResults' => $shows['results']]);

        return view('batch', [
            'movies'         => $shows,
            'user_input'     => session('tvInput'),
            'all_genres'     => $all_genres,
            'movie_genres'   => $movie_genres,
            'providersArray' => $movieService->buildProvidersArray($tmdb),
            'tag'            => 'TV Shows picked for you',
            'savedIds'       => $this->savedWatchlistIds(),
            'mediaType'      => 'tv',
        ]);
    }

    public function criteriaRoll(TvCriteriaRequest $request, MovieService $movieService, TmdbClient $tmdb): JsonResponse
    {
        $country   = $movieService->getUserCountry();
        $submitted = $this->submitted($request);
        $criteria  = $movieService->resolveSessionCriteria($submitted, $request->isMethod('post') && !empty($submitted), self::SESSION_KEY, self::CLEAR_WITH);
        $criteria['page'] = $movieService->resolvePage($tmdb, $criteria, $country, 'tv');

        $results = $tmdb->discoverTv($criteria, $country);
        $picked  = $movieService->pickBatch($results['results'] ?? []);

        session([
            'lastBatchResults'  => $picked,
            'lastBatchType'     => 'tv',
            'batchUrl'          => url('/tv/multiple'),
            'savedBatchUrl'     => url('/tv/multiple'),
            'savedBatchResults' => $picked,
        ]);

        return response()->json($this->toRollCards($picked, 'tv'));
    }

    public function homepageRoll(MovieService $movieService, TmdbClient $tmdb): JsonResponse
    {
        session([self::SESSION_KEY => self::DEFAULTS]);

        $country = $movieService->getUserCountry();
        $results = $tmdb->discoverTv([
            'sort_by'          => 'popularity.desc',
            'page'             => rand(1, 20),
            'vote_average.gte' => 5,
        ], $country);

        $picked = $movieService->pickBatch($results['results'] ?? []);

        session([
            'lastBatchResults'  => $picked,
            'lastBatchType'     => 'tv',
            'batchUrl'          => url('/tv/multiple'),
            'savedBatchUrl'     => url('/tv/multiple'),
            'savedBatchResults' => $picked,
        ]);

        return response()->json($this->toRollCards($picked, 'tv'));
    }
}
