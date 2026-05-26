<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use App\Services\MovieService;
use App\Http\Requests\TvCriteriaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TvPickController extends Controller
{
    public function single(TvCriteriaRequest $request, MovieService $movieService, TmdbClient $tmdb): RedirectResponse
    {
        if ($redirect = $this->handleSessionReset($request, '/tv/pick')) {
            return $redirect;
        }

        $country  = $movieService->getUserCountry();
        $criteria = $this->resolveSessionCriteria($this->submitted($request), $request->isMethod('post'));
        $criteria['page'] = $movieService->resolvePage($tmdb, $criteria, $country, 'tv');

        $results = $tmdb->discoverTv($criteria, $country);

        if (empty($results['results'])) {
            $criteria['page'] = $movieService->randomPage(session('tvInput.total_pages', 500), $criteria['page']);
            $results = $tmdb->discoverTv($criteria, $country);
        }

        if (empty($results['results'])) {
            return redirect('/tv/criteria');
        }

        return redirect()->route('tv.show', [$movieService->randomMovie($results['results'])['id']]);
    }

    public function batch(TvCriteriaRequest $request, MovieService $movieService, TmdbClient $tmdb): View|RedirectResponse
    {
        if ($redirect = $this->handleSessionReset($request, '/tv/multiple')) {
            return $redirect;
        }

        $country = $movieService->getUserCountry();

        if ($request->query('from') === 'roll' && session('lastBatchType') === 'tv' && session('lastBatchResults')) {
            $results = session('lastBatchResults');
            session()->forget(['lastBatchResults', 'lastBatchType']);
            $shows = ['results' => $results];
        } else {
            $criteria = $this->resolveSessionCriteria($this->submitted($request), $request->isMethod('post'));
            $criteria['page'] = $movieService->resolvePage($tmdb, $criteria, $country, 'tv');

            $shows            = $tmdb->discoverTv($criteria, $country);
            $shows['results'] = $movieService->pickBatch($shows['results']);

            // Normalise TV fields so the shared carousel component works
            $shows['results'] = array_map(function ($show) {
                $show['title']        = $show['name'] ?? $show['title'] ?? '';
                $show['release_date'] = $show['first_air_date'] ?? '';
                return $show;
            }, $shows['results']);
        }

        $all_genres   = $movieService->genres($tmdb, 'tv');
        $movie_genres = $movieService->movieGenresMap($shows['results'], $all_genres);

        session(['batchUrl' => url('/tv/multiple')]);

        $savedIds = auth()->check()
            ? auth()->user()->watchlist()->pluck('tmdb_id')->toArray()
            : [];

        return view('tv.batch', [
            'movies'         => $shows,
            'user_input'     => session('tvInput'),
            'all_genres'     => $all_genres,
            'movie_genres'   => $movie_genres,
            'providersArray' => $movieService->buildProvidersArray($tmdb),
            'tag'            => 'TV Shows picked for you',
            'savedIds'       => $savedIds,
        ]);
    }

    private function resolveSessionCriteria(array $submitted, bool $overwrite = false): array
    {
        if ($overwrite || !empty($submitted)) {
            session()->put('tvInput', $submitted);
            session()->forget('tvPersonRollIds');
        } elseif (session('tvInput') === null) {
            session()->put('tvInput', $submitted);
        }

        return session('tvInput') ?? [];
    }

    private function submitted(TvCriteriaRequest $request): array
    {
        return array_filter(
            $request->except(['_token', 'i', 'total_pages', 'a']),
            fn($v) => $v !== '' && $v !== null && $v !== []
        );
    }

    private function handleSessionReset(TvCriteriaRequest $request, string $redirectTo): ?RedirectResponse
    {
        if ($request->query('i') === 'new') {
            session()->forget('tvPersonRollIds');
            session(['tvInput' => [
                'with_original_language' => 'en',
                'first_air_date_gte'     => 1990,
                'vote_average_gte'       => 7,
                'vote_count_gte'         => 100,
            ]]);
            return null;
        }

        if ($request->query('i') !== null && session('tvInput') !== null) {
            session()->forget(['tvInput', 'tvPersonRollIds']);
            return redirect(url($redirectTo));
        }

        if ($request->query('a') !== null) {
            session()->forget('tvInput');
        }

        return null;
    }

    public function criteriaRollJson(TvCriteriaRequest $request, MovieService $movieService, TmdbClient $tmdb): JsonResponse
    {
        $country   = $movieService->getUserCountry();
        $submitted = $this->submitted($request);
        $criteria  = $this->resolveSessionCriteria($submitted, $request->isMethod('post') && !empty($submitted));
        $criteria['page'] = $movieService->resolvePage($tmdb, $criteria, $country, 'tv');

        $results = $tmdb->discoverTv($criteria, $country);
        $picked  = $movieService->pickBatch($results['results'] ?? []);

        session(['lastBatchResults' => $picked, 'lastBatchType' => 'tv']);
        session()->forget('batchUrl');

        return response()->json(array_map(fn($m) => [
            'title'        => $m['name'] ?? $m['title'] ?? '',
            'poster_path'  => $m['poster_path'] ?? null,
            'vote_average' => $m['vote_average'] ?? 0,
            'url'          => route('tv.show', $m['id']),
        ], $picked));
    }

    public function rollJson(MovieService $movieService, TmdbClient $tmdb): JsonResponse
    {
        session(['tvInput' => [
            'with_original_language' => 'en',
            'first_air_date_gte'     => 1990,
            'vote_average_gte'       => 7,
            'vote_count_gte'         => 100,
        ]]);
        session()->forget('batchUrl');

        $country = $movieService->getUserCountry();
        $results = $tmdb->discoverTv([
            'sort_by'          => 'popularity.desc',
            'page'             => rand(1, 20),
            'vote_average.gte' => 5,
        ], $country);

        $picked = $movieService->pickBatch($results['results'] ?? []);

        session(['lastBatchResults' => $picked, 'lastBatchType' => 'tv']);

        return response()->json(array_map(fn($m) => [
            'title'        => $m['name'] ?? $m['title'] ?? '',
            'poster_path'  => $m['poster_path'] ?? null,
            'vote_average' => $m['vote_average'] ?? 0,
            'url'          => route('tv.show', $m['id']),
        ], $picked));
    }
}
