<?php

namespace App\Http\Controllers;

use App\Services\MovieService;
use App\Services\TmdbClient;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(TmdbClient $tmdb, MovieService $movieService): View
    {
        // Homepage visit breaks any prior batch context
        session()->forget('batchUrl');

        $savedIds = $this->savedWatchlistIds();

        $normalize = fn(array $data) => array_merge($data, [
            'results' => array_map(fn($s) => array_merge($s, [
                'title'        => $s['name'] ?? $s['title'] ?? '',
                'release_date' => $s['first_air_date'] ?? $s['release_date'] ?? '',
            ]), $data['results'] ?? []),
        ]);

        $trendingDay   = $tmdb->trending('day');
        $tvTrendingDay = $normalize($tmdb->trendingTv('day'));

        $movieGenres = $movieService->genres($tmdb, 'movie');
        $tvGenres    = $movieService->genres($tmdb, 'tv');

        return view('home', [
            'trendingDay'        => $trendingDay,
            'tvTrendingDay'      => $tvTrendingDay,
            'savedIds'           => $savedIds,
            'trendingGenres'     => $movieService->movieGenresMap($trendingDay['results']   ?? [], $movieGenres),
            'tvTrendingGenres'   => $movieService->movieGenresMap($tvTrendingDay['results'] ?? [], $tvGenres),
        ]);
    }
}
