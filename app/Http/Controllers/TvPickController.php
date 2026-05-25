<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use App\Services\MovieService;
use App\Http\Requests\TvCriteriaRequest;
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
        $criteria['page'] = $movieService->resolveTvPage($tmdb, $criteria, $country);

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

        $country  = $movieService->getUserCountry();
        $criteria = $this->resolveSessionCriteria($this->submitted($request), $request->isMethod('post'));
        $criteria['page'] = $movieService->resolveTvPage($tmdb, $criteria, $country);

        $shows            = $tmdb->discoverTv($criteria, $country);
        $shows['results'] = $movieService->pickBatch($shows['results']);

        // Normalise TV fields so the shared carousel component works
        $shows['results'] = array_map(function ($show) {
            $show['title']        = $show['name'] ?? $show['title'] ?? '';
            $show['release_date'] = $show['first_air_date'] ?? '';
            return $show;
        }, $shows['results']);

        $all_genres   = $movieService->tvGenres($tmdb);
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
}
