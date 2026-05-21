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
            session()->forget(['userInput', 'batchUrl']);
        }

        $movieCriteria = session('userInput');
        $batchUrl      = session('batchUrl');
        $similarMovies = null;
        $similarTitle  = 'Similar Movies';
        $linkSuffix    = '';

        $wlStatus = $request->query('wl_status');
        $wlGenres = $request->query('wl_genres', '');

        if ($wlStatus && auth()->check()) {
            $linkSuffix = '?wl_status=' . urlencode($wlStatus) . ($wlGenres ? '&wl_genres=' . urlencode($wlGenres) : '');

            $wlQuery = auth()->user()->watchlist()->where('tmdb_id', '!=', $movieId);
            if ($wlStatus !== 'all') {
                $wlQuery->where('status', $wlStatus);
            }
            $wlItems = $wlQuery->get();

            if ($wlGenres) {
                $genreList = array_map('trim', explode(',', $wlGenres));
                $wlItems = $wlItems->filter(function ($item) use ($genreList) {
                    if (!$item->genres) return false;
                    $cardGenres = array_map('trim', explode(',', $item->genres));
                    return !empty(array_intersect($genreList, $cardGenres));
                });
            }

            if ($wlItems->isNotEmpty()) {
                $similarMovies = ['results' => $wlItems->map(fn($item) => [
                    'id'           => $item->tmdb_id,
                    'title'        => $item->title,
                    'poster_path'  => $item->poster_path,
                    'release_date' => $item->year ? $item->year . '-01-01' : null,
                ])->values()->all()];
                $similarTitle = 'More from Your Watchlist';
            }
        } elseif (!empty($movieCriteria)) {
            $movieCriteria['page'] = rand(1, min($movieCriteria['total_pages'] ?? 500, 500));
            $discovered = $tmdb->discover($movieCriteria, $country);
            if (count($discovered['results']) >= 4) {
                $similarMovies = $discovered;
                $similarTitle  = 'More with Same Criteria';
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
        $savedIds   = auth()->check()
            ? auth()->user()->watchlist()->pluck('tmdb_id')->toArray()
            : [];

        return view('movie', compact(
            'tmdbInfo', 'omdbInfo', 'urls', 'similarMovies', 'similarTitle', 'linkSuffix', 'genres',
            'trailer', 'user_input', 'all_genres', 'watchProviders', 'providersArray', 'batchUrl', 'savedIds'
        ));
    }
}
