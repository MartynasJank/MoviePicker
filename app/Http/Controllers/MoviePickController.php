<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use App\Services\MovieService;
use App\Http\Requests\CriteriaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MoviePickController extends Controller
{
    public function single(CriteriaRequest $request, MovieService $movieService, TmdbClient $tmdb): RedirectResponse
    {
        if ($redirect = $this->handleSessionReset($request, '/movie')) {
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

        return redirect()->route('movie', [$movieService->randomMovie($results['results'])['id']]);
    }

    public function batch(CriteriaRequest $request, MovieService $movieService, TmdbClient $tmdb): View|RedirectResponse
    {
        if ($redirect = $this->handleSessionReset($request, '/multiple')) {
            return $redirect;
        }

        $country = $movieService->getUserCountry();

        if ($request->query('from') === 'roll' && session('lastBatchType') === 'movie' && session('lastBatchResults')) {
            $results = session('lastBatchResults');
            session()->forget(['lastBatchResults', 'lastBatchType']);
            $movies = ['results' => $results];
        } else {
            $criteria = $movieService->resolveSessionCriteria($this->submitted($request), $request->isMethod('post'));
            $criteria['page'] = $movieService->resolvePage($tmdb, $criteria, $country);
            $movies            = $tmdb->discover($criteria, $country);
            $movies['results'] = $movieService->pickBatch($movies['results']);
        }

        $all_genres = $movieService->genres($tmdb);
        $movie_genres       = $movieService->movieGenresMap($movies['results'], $all_genres);

        session(['batchUrl' => url('/multiple')]);

        $savedIds = $this->savedWatchlistIds();

        return view('batch', [
            'movies'         => $movies,
            'user_input'     => session('userInput'),
            'all_genres'     => $all_genres,
            'movie_genres'   => $movie_genres,
            'providersArray' => $movieService->buildProvidersArray($tmdb),
            'tag'            => 'Movies picked for you',
            'savedIds'       => $savedIds,
        ]);
    }

    private function submitted(CriteriaRequest $request): array
    {
        return array_filter(
            $request->except(['_token', 'i', 'total_pages', 'a']),
            fn($v) => $v !== '' && $v !== null && $v !== []
        );
    }

    private function handleSessionReset(CriteriaRequest $request, string $redirectTo): ?RedirectResponse
    {
        if ($request->query('i') === 'new') {
            session(['userInput' => [
                'with_original_language'   => 'en',
                'primary_release_date_gte' => 1990,
                'vote_average_gte'         => 7,
                'vote_count_gte'           => 100,
            ]]);
            return null;
        }

        if ($request->query('i') !== null && session('userInput') !== null) {
            session()->forget('userInput');
            return redirect(url($redirectTo));
        }

        if ($request->query('a') !== null) {
            session()->forget('userInput');
        }

        return null;
    }

    public function criteriaRollJson(CriteriaRequest $request, MovieService $movieService, TmdbClient $tmdb): JsonResponse
    {
        $country   = $movieService->getUserCountry();
        $submitted = $this->submitted($request);
        $criteria  = $movieService->resolveSessionCriteria($submitted, $request->isMethod('post') && !empty($submitted));
        $criteria['page'] = $movieService->resolvePage($tmdb, $criteria, $country);

        $results = $tmdb->discover($criteria, $country);
        $picked  = $movieService->pickBatch($results['results'] ?? []);

        session(['lastBatchResults' => $picked, 'lastBatchType' => 'movie']);
        session()->forget('batchUrl');

        return response()->json($this->toRollCards($picked));
    }

    public function rollJson(MovieService $movieService, TmdbClient $tmdb): JsonResponse
    {
        session(['userInput' => [
            'with_original_language'   => 'en',
            'primary_release_date_gte' => 1990,
            'vote_average_gte'         => 7,
            'vote_count_gte'           => 100,
        ]]);
        session()->forget('batchUrl');

        $country = $movieService->getUserCountry();
        $results = $tmdb->discover([
            'sort_by'            => 'popularity.desc',
            'page'               => rand(1, 20),
            'vote_count.gte'     => 50,
            'vote_average.gte'   => 5,
        ], $country);

        $picked = $movieService->pickBatch($results['results'] ?? []);

        session(['lastBatchResults' => $picked, 'lastBatchType' => 'movie']);

        return response()->json($this->toRollCards($picked));
    }
}
