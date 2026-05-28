<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use App\Services\OmdbClient;
use App\Services\MovieService;
use App\Services\RatingsUrlBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TvShowController extends Controller
{
    public function __invoke(Request $request, TmdbClient $tmdb, OmdbClient $omdb, MovieService $movieService, RatingsUrlBuilder $link): View
    {
        $showId  = $request->route('id');
        $country = $movieService->getUserCountry();

        try {
            $tmdbInfo = $tmdb->tvShow($showId);
            if (empty($tmdbInfo->id)) abort(404);
        } catch (\Throwable) {
            abort(404);
        }

        $imdbId   = $tmdbInfo->external_ids->imdb_id ?? null;
        $omdbInfo = $omdb->tv($imdbId);
        $urls     = $link->links($omdbInfo, 'tv');

        $watchProviders = isset($tmdbInfo->{'watch/providers'}->results->$country->flatrate)
            ? $tmdbInfo->{'watch/providers'}->results->$country
            : null;

        $providersArray = $movieService->buildProvidersArray($tmdb);

        if ($request->query('i') !== null) {
            session()->forget(['tvInput', 'batchUrl']);
        }

        $showCriteria = session('tvInput');
        $batchUrl     = session('batchUrl');

        $similarShows = null;
        $similarTitle = 'Similar Shows';

        if (!empty($showCriteria)) {
            $showCriteria['page'] = rand(1, min($showCriteria['total_pages'] ?? 500, 500));
            $discovered = $tmdb->discoverTv($showCriteria, $country);
            if (count($discovered['results']) >= 4) {
                $discovered['results'] = $movieService->normaliseShows($discovered['results']);
                $similarShows = $discovered;
                $similarTitle = 'More with Same Criteria';
            }
        }

        if ($similarShows === null) {
            $raw = $tmdb->similarShows($tmdbInfo);
            if ($raw) {
                $raw['results'] = $movieService->normaliseShows($raw['results']);
                $similarShows   = $raw;
            }
        }

        $genres     = $movieService->genresString($tmdbInfo);
        $trailer    = $movieService->getTrailer($tmdbInfo->videos->results ?? []);
        $all_genres = $movieService->genres($tmdb, 'tv');
        $user_input = session('tvInput', 'default');
        $savedIds   = $this->savedWatchlistIds();

        return view('tv.show', compact(
            'tmdbInfo', 'omdbInfo', 'urls', 'genres', 'trailer', 'user_input', 'all_genres',
            'watchProviders', 'providersArray', 'batchUrl', 'savedIds', 'country',
            'similarShows', 'similarTitle'
        ));
    }


}
