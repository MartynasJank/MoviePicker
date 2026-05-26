<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use App\Services\MovieService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TvShowController extends Controller
{
    public function __invoke(Request $request, TmdbClient $tmdb, MovieService $movieService): View
    {
        $showId  = $request->route('id');
        $country = $movieService->getUserCountry();

        try {
            $tmdbInfo = $tmdb->tvShow($showId);
            if (empty($tmdbInfo->id)) abort(404);
        } catch (\Throwable) {
            abort(404);
        }

        $watchProviders = isset($tmdbInfo->{'watch/providers'}->results->$country->flatrate)
            ? $tmdbInfo->{'watch/providers'}->results->$country
            : null;

        $providersArray = $movieService->buildProvidersArray($tmdb);

        if ($request->query('i') !== null) {
            session()->forget(['tvInput', 'batchUrl']);
        }

        $showCriteria = session('tvInput');
        $batchUrl     = session('batchUrl');

        if ($batchUrl) {
            $referer = $request->headers->get('referer', '');
            if (!$referer || !str_starts_with($referer, config('app.url'))) {
                session()->forget('batchUrl');
                $batchUrl = null;
            }
        }

        $similarShows = null;
        $similarTitle = 'Similar Shows';

        if (!empty($showCriteria)) {
            $showCriteria['page'] = rand(1, min($showCriteria['total_pages'] ?? 500, 500));
            $discovered = $tmdb->discoverTv($showCriteria, $country);
            if (count($discovered['results']) >= 4) {
                $discovered['results'] = $this->normaliseShows($discovered['results']);
                $similarShows = $discovered;
                $similarTitle = 'More with Same Criteria';
            }
        }

        if ($similarShows === null) {
            $raw = $tmdb->similarShows($tmdbInfo);
            if ($raw) {
                $raw['results'] = $this->normaliseShows($raw['results']);
                $similarShows   = $raw;
            }
        }

        $genres     = $movieService->genresString($tmdbInfo);
        $trailer    = $movieService->getTrailer($tmdbInfo->videos->results ?? []);
        $all_genres = $movieService->genres($tmdb, 'tv');
        $user_input = session('tvInput', 'default');
        $savedIds   = auth()->check()
            ? auth()->user()->watchlist()->pluck('tmdb_id')->toArray()
            : [];

        return view('tv.show', compact(
            'tmdbInfo', 'genres', 'trailer', 'user_input', 'all_genres',
            'watchProviders', 'providersArray', 'batchUrl', 'savedIds', 'country',
            'similarShows', 'similarTitle'
        ));
    }

    private function normaliseShows(array $shows): array
    {
        return array_map(function ($show) {
            $show['title']        = $show['name'] ?? $show['title'] ?? '';
            $show['release_date'] = $show['first_air_date'] ?? '';
            return $show;
        }, $shows);
    }
}
