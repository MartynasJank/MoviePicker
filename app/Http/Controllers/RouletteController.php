<?php

namespace App\Http\Controllers;

use App\Models\Roulette;
use App\Models\Setting;
use App\Services\TmdbClient;
use App\Services\MovieService;
use App\Services\RouletteTagMapper;
use Illuminate\View\View;

class RouletteController extends Controller
{
    public function index(TmdbClient $tmdb): View
    {
        $roulettes = Roulette::where('is_public', true)
            ->where('is_system', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $mapper = new RouletteTagMapper();

        foreach ($roulettes->whereNull('poster_paths') as $roulette) {
            try {
                $tagsForPosters = array_diff_key($roulette->tags, ['platform' => true]);
                $criteria       = $roulette->media_type === 'tv'
                    ? $mapper->toCriteriaTv($tagsForPosters)
                    : $mapper->toCriteria($tagsForPosters);
                $criteria['page'] = 1;

                $results = $roulette->media_type === 'tv'
                    ? $tmdb->discoverTv($criteria, 'US')
                    : $tmdb->discover($criteria, 'US');

                // Fallback: if genre filter returned nothing (can happen with non-native TV genre IDs),
                // retry without genre so the roulette card still gets a representative poster.
                if ($roulette->media_type === 'tv' && empty($results['results']) && isset($criteria['with_genres'])) {
                    $results = $tmdb->discoverTv(array_diff_key($criteria, ['with_genres' => true]), 'US');
                }

                $paths = [];
                foreach ($results['results'] ?? [] as $item) {
                    if (!empty($item['poster_path'])) {
                        $paths[] = $item['poster_path'];
                        if (count($paths) >= 8) break;
                    }
                }
                $roulette->update(['poster_paths' => $paths ?: null]);
            } catch (\Throwable) {
                // retry next request
            }
        }

        $movieRoulettes = $roulettes->where('media_type', 'movie')->values();
        $tvRoulettes    = $roulettes->where('media_type', 'tv')->values();

        $movieDefaultRowOrder = ['By Decade', 'Netflix', 'Prime Video', 'HBO', 'Disney+', 'Apple TV+', 'World Cinema', 'Anime', 'By Genre'];
        $tvDefaultRowOrder    = ['By Decade', 'Netflix', 'Prime Video', 'HBO', 'Disney+', 'Apple TV+', 'World TV', 'Anime', 'By Genre'];

        $movieRowOrder = Setting::get('roulette_row_order', $movieDefaultRowOrder);
        $tvRowOrder    = Setting::get('roulette_tv_row_order', $tvDefaultRowOrder);

        $movieGrouped = $movieRoulettes->groupBy(fn(Roulette $r) => $r->groupName());
        $movieGrouped = collect($movieRowOrder)
            ->filter(fn($g) => $movieGrouped->has($g))
            ->mapWithKeys(fn($g) => [$g => $movieGrouped[$g]->sortBy('sort_order')->values()]);

        $tvGrouped = $tvRoulettes->groupBy(fn(Roulette $r) => $r->groupName());
        $tvGrouped = collect($tvRowOrder)
            ->filter(fn($g) => $tvGrouped->has($g))
            ->mapWithKeys(fn($g) => [$g => $tvGrouped[$g]->sortBy('sort_order')->values()]);

        // Community and user roulettes
        $communityRoulettes = Roulette::where('is_system', false)
            ->where('is_public', true)
            ->orderBy('created_at', 'desc')
            ->get();

        $myRoulettes = auth()->check()
            ? Roulette::where('user_id', auth()->id())->orderBy('created_at', 'desc')->get()
            : collect();

        return view('roulettes', compact('movieGrouped', 'tvGrouped', 'communityRoulettes', 'myRoulettes'));
    }

    public function pick(string $slug, MovieService $movieService, TmdbClient $tmdb): View
    {
        $roulette = Roulette::where('slug', $slug)
            ->where(function ($q) {
                $q->where('is_public', true)
                  ->orWhere('user_id', auth()->id());
            })
            ->firstOrFail();

        $mapper  = new RouletteTagMapper();
        $country = $movieService->getUserCountry();
        $isTv    = $roulette->media_type === 'tv';

        $savedIds = auth()->check()
            ? auth()->user()->watchlist()->pluck('tmdb_id')->toArray()
            : [];

        if ($isTv) {
            $base     = $mapper->toCriteriaTv($roulette->tags);
            $criteria = $movieService->resolveRoulettePageTv($tmdb, $base, $slug, $country);
            $shows    = $tmdb->discoverTv($criteria, $country);

            $rawResults = $shows['results'] ?? [];
            foreach ($rawResults as &$show) {
                $show['title']        = $show['name'] ?? '';
                $show['release_date'] = $show['first_air_date'] ?? '';
            }
            unset($show);

            $shows['results'] = $movieService->pickBatch($rawResults);
            $allGenres        = $movieService->tvGenres($tmdb);

            session(['tvInput'  => array_merge($criteria, ['total_pages' => $shows['total_pages'] ?? 500])]);
            session(['batchUrl' => request()->url()]);

            return view('tv.batch', [
                'movies'       => $shows,
                'movie_genres' => $movieService->movieGenresMap($shows['results'], $allGenres),
                'tag'          => $roulette->name,
                'title'        => $roulette->name . ' — MoviePickr',
                'savedIds'     => $savedIds,
            ]);
        }

        $base                = $mapper->toCriteria($roulette->tags);
        $criteria            = $movieService->resolveRoulettePage($tmdb, $base, $slug, $country);
        $movies              = $tmdb->discover($criteria, $country);
        $movies['results']   = $movieService->pickBatch($movies['results']);
        $all_genres          = $movieService->genres($tmdb);

        session(['userInput' => array_merge($criteria, ['total_pages' => $movies['total_pages'] ?? 500])]);
        session(['batchUrl'  => request()->url()]);

        return view('batch', [
            'movies'       => $movies,
            'movie_genres' => $movieService->movieGenresMap($movies['results'], $all_genres),
            'tag'          => $roulette->name,
            'title'        => $roulette->name . ' — MoviePickr',
            'savedIds'     => $savedIds,
        ]);
    }
}
