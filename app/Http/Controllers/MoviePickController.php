<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use App\Services\MovieService;
use App\Http\Requests\CriteriaRequest;
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
        $criteria = $movieService->resolveSessionCriteria($this->submitted($request));
        $criteria['page'] = $movieService->resolvePage($tmdb, $criteria, $country);

        $results = $tmdb->discover($criteria, $country);

        return redirect()->route('movie', [$movieService->randomMovie($results['results'])['id']]);
    }

    public function batch(CriteriaRequest $request, MovieService $movieService, TmdbClient $tmdb): View|RedirectResponse
    {
        if ($redirect = $this->handleSessionReset($request, '/multiple')) {
            return $redirect;
        }

        $country  = $movieService->getUserCountry();
        $criteria = $movieService->resolveSessionCriteria($this->submitted($request));
        $criteria['page'] = $movieService->resolvePage($tmdb, $criteria, $country);

        $movies             = $tmdb->discover($criteria, $country);
        $movies['results']  = $movieService->pickBatch($movies['results']);
        $all_genres         = $movieService->genres($tmdb);
        $movie_genres       = $movieService->movieGenresMap($movies['results'], $all_genres);

        return view('batch', [
            'movies'        => $movies,
            'user_input'    => session('userInput'),
            'all_genres'    => $all_genres,
            'movie_genres'  => $movie_genres,
            'providersArray' => $movieService->buildProvidersArray($tmdb),
            'tag'           => 'Movies picked for you',
        ]);
    }

    private function submitted(CriteriaRequest $request): array
    {
        return $request->except(['_token', 'i', 'total_pages']);
    }

    private function handleSessionReset(CriteriaRequest $request, string $redirectTo): ?RedirectResponse
    {
        if ($request->query('i') !== null && session('userInput') !== null) {
            session()->forget('userInput');
            return redirect(url($redirectTo));
        }

        if ($request->query('a') !== null) {
            session()->forget('userInput');
        }

        return null;
    }
}
