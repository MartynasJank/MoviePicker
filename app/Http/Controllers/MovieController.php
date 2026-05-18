<?php

namespace App\Http\Controllers;

use App\Services\OmdbClient;
use App\Services\TmdbClient;
use App\Services\MovieService;
use App\Services\RatingsUrlBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MovieController extends Controller
{
    public function __invoke(Request $request, TmdbClient $tmdb, OmdbClient $omdb, MovieService $movieService, RatingsUrlBuilder $link): View
    {
        $movieId  = $request->route('id');
        $country  = $movieService->getUserCountry();
        $tmdbInfo = $tmdb->movie($movieId);
        $omdbInfo = $omdb->movie($tmdbInfo->imdb_id);

        $watchProviders = isset($tmdbInfo->{'watch/providers'}->results->$country->flatrate)
            ? $tmdbInfo->{'watch/providers'}->results->$country
            : null;

        $providersArray = $movieService->buildProvidersArray($tmdb);

        if ($request->query('i') !== null) {
            session()->forget('userInput');
        }

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
