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

        // Fetch and persist poster paths for any roulette that doesn't have them yet.
        // Runs once per roulette ever; subsequent loads read from DB.
        $mapper = new RouletteTagMapper();
        foreach ($roulettes->whereNull('poster_paths') as $roulette) {
            try {
                // Strip platform for poster fetch — provider IDs vary by region.
                // Genre/era/language tags are enough to get representative images.
                $tagsForPosters = array_diff_key($roulette->tags, ['platform' => true]);
                $criteria         = $mapper->toCriteria($tagsForPosters);
                $criteria['page'] = 1;
                $results          = $tmdb->discover($criteria, 'US');
                $paths            = [];
                foreach ($results['results'] ?? [] as $movie) {
                    if (!empty($movie['poster_path'])) {
                        $paths[] = $movie['poster_path'];
                        break;
                    }
                }
                $roulette->update(['poster_paths' => $paths ?: null]);
            } catch (\Throwable) {
                // leave null, will retry next request
            }
        }

        $grouped = $roulettes->groupBy(fn(Roulette $r) => $r->groupName());

        $defaultRowOrder = ['By Decade', 'Netflix', 'Prime Video', 'HBO', 'Disney+', 'Apple TV+', 'World Cinema', 'Anime', 'Community', 'By Genre'];
        $rowOrder = Setting::get('roulette_row_order', $defaultRowOrder);

        $grouped = collect($rowOrder)
            ->filter(fn($g) => $grouped->has($g))
            ->mapWithKeys(fn($g) => [$g => $grouped[$g]->sortBy('sort_order')->values()]);

        // Community: public user-created roulettes
        $communityRoulettes = Roulette::where('is_system', false)
            ->where('is_public', true)
            ->orderBy('created_at', 'desc')
            ->get();

        // My Roulettes: current user's own (auth-gated)
        $myRoulettes = auth()->check()
            ? Roulette::where('user_id', auth()->id())->orderBy('created_at', 'desc')->get()
            : collect();

        return view('roulettes', compact('grouped', 'communityRoulettes', 'myRoulettes'));
    }

    public function pick(string $slug, MovieService $movieService, TmdbClient $tmdb): View
    {
        $roulette = Roulette::where('slug', $slug)->where('is_public', true)->firstOrFail();

        $mapper   = new RouletteTagMapper();
        $base     = $mapper->toCriteria($roulette->tags);
        $country  = $movieService->getUserCountry();

        $criteria          = $movieService->resolveRoulettePage($tmdb, $base, $slug, $country);
        $movies            = $tmdb->discover($criteria, $country);
        $movies['results'] = $movieService->pickBatch($movies['results']);
        $all_genres        = $movieService->genres($tmdb);

        return view('batch', [
            'movies'       => $movies,
            'movie_genres' => $movieService->movieGenresMap($movies['results'], $all_genres),
            'tag'          => $roulette->name,
            'title'        => $roulette->name . ' — MoviePickr',
        ]);
    }
}