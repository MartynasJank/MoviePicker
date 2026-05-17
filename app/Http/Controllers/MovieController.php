<?php

namespace App\Http\Controllers;

use App\OMDB;
use App\TMDB;
use Illuminate\Http\Request;
use App\Services\MovieService;
use App\Services\UrlGenerator;

class MovieController extends Controller
{
    public function show(Request $request, TMDB $tmdb, OMDB $omdb, MovieService $movieService, UrlGenerator $link)
    {
        $movieId  = $request['id'];
        $country  = $movieService->getUserCountry();
        $tmdbInfo = $tmdb->movie($movieId);
        $omdbInfo = $omdb->movie($tmdbInfo->imdb_id);

        $watchProviders = isset($tmdbInfo->{'watch/providers'}->results->$country->flatrate)
            ? $tmdbInfo->{'watch/providers'}->results->$country
            : null;

        $providersArray = $movieService->buildProvidersArray($tmdb);

        $movieCriteria = session('userInput');
        $similarMovies = null;

        if (!empty($movieCriteria)) {
            $movieCriteria['page'] = rand(1, min($movieCriteria['total_pages'] ?? 500, 500));
            $discovered = $tmdb->discover($movieCriteria, $country);
            if (count($discovered['results']) >= 4) {
                $similarMovies = $discovered;
            }
        }

        if ($similarMovies === null) {
            $similarMovies = $tmdb->similarMovies($tmdbInfo);
        }

        $genres     = $movieService->genresString($tmdbInfo);
        $urls       = $link->linksArray($omdbInfo);
        $trailer    = $movieService->getTrailer($tmdbInfo->videos->results ?? []);
        $all_genres = $movieService->genres($tmdb);
        $user_input = $request->session()->get('userInput', 'default');

        return view('movie', compact(
            'tmdbInfo', 'omdbInfo', 'urls', 'similarMovies', 'genres',
            'trailer', 'user_input', 'all_genres', 'watchProviders', 'providersArray'
        ));
    }
}
