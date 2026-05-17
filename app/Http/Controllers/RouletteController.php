<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use App\Services\MovieService;
use Illuminate\View\View;

class RouletteController extends Controller
{
    private const ROULETTES = [
        'horror' => [
            'criteria' => ['with_genres' => [27], 'with_watch_providers' => [8]],
            'tag'      => 'Netflix Horror Movies',
            'title'    => 'Netflix Horror — MoviePickr',
        ],
        'doc' => [
            'criteria' => ['with_genres' => [99], 'with_watch_providers' => [8]],
            'tag'      => 'Netflix Documentaries',
            'title'    => 'Netflix Documentaries — MoviePickr',
        ],
        'animovies' => [
            'criteria' => ['with_genres' => [16], 'with_watch_providers' => [8], 'with_original_language' => 'ja'],
            'tag'      => 'Netflix Anime Movies',
            'title'    => 'Netflix Anime Movies — MoviePickr',
        ],
    ];

    public function index(): View
    {
        return view('roulettes');
    }

    public function pick(string $type, MovieService $movieService, TmdbClient $tmdb): View
    {
        $config  = self::ROULETTES[$type] ?? abort(404);
        $country = $movieService->getUserCountry();

        $criteria            = $movieService->resolveRoulettePage($tmdb, $config['criteria'], $type, $country);
        $movies              = $tmdb->discover($criteria, $country);
        $movies['results']   = $movieService->pickBatch($movies['results']);
        $all_genres          = $movieService->genres($tmdb);

        return view('batch', [
            'movies'       => $movies,
            'movie_genres' => $movieService->movieGenresMap($movies['results'], $all_genres),
            'tag'          => $config['tag'],
            'title'        => $config['title'],
        ]);
    }
}
