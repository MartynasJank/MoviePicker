<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use App\Services\MovieService;
use Illuminate\Http\RedirectResponse;

class PersonRollController extends Controller
{
    public function movie(int $id, TmdbClient $tmdb, MovieService $movieService): RedirectResponse
    {
        $country  = $movieService->getUserCountry();
        $criteria = ['with_cast' => [$id]];

        session(['userInput' => $criteria]);

        $criteria['page'] = $movieService->resolvePage($tmdb, $criteria, $country);
        $results = $tmdb->discover($criteria, $country);

        return redirect()->route('movie', [$movieService->randomMovie($results['results'])['id']]);
    }

    public function tv(int $id, TmdbClient $tmdb, MovieService $movieService): RedirectResponse
    {
        $country  = $movieService->getUserCountry();
        $criteria = ['with_people' => [$id]];

        session(['tvInput' => $criteria]);

        $criteria['page'] = $movieService->resolveTvPage($tmdb, $criteria, $country);
        $results = $tmdb->discoverTv($criteria, $country);

        return redirect()->route('tv.show', [$movieService->randomMovie($results['results'])['id']]);
    }
}
