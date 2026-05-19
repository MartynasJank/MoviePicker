<?php

namespace App\Http\Controllers;

use App\Models\Roulette;
use App\Services\TmdbClient;
use App\Services\MovieService;
use App\Services\RouletteTagMapper;
use Illuminate\View\View;

class RouletteController extends Controller
{
    public function index(TmdbClient $tmdb): View
    {
        $roulettes = Roulette::where('is_public', true)
            ->orderBy('is_system', 'desc')
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

        $platformLabels = [
            'netflix' => 'Netflix',
            'prime'   => 'Prime Video',
            'hbo'     => 'HBO',
            'disney'  => 'Disney+',
            'apple'   => 'Apple TV+',
        ];

        $grouped = $roulettes->groupBy(function (Roulette $r) use ($platformLabels) {
            $tags = $r->tags;
            if (!empty($tags['platform'])) {
                return $platformLabels[$tags['platform'][0]] ?? 'Other';
            }
            if (!empty($tags['era'])) return 'By Decade';
            // Anime: animation genre + Japanese language (without platform)
            if (!empty($tags['language']) && in_array('ja', (array) $tags['language'])
                && !empty($tags['genre']) && in_array('animation', (array) $tags['genre'])) {
                return 'Anime';
            }
            if (!empty($tags['language'])) return 'World Cinema';
            return 'By Genre';
        });

        $groupOrder = ['By Decade', ...array_values($platformLabels), 'World Cinema', 'Anime', 'By Genre'];
        $grouped    = collect($groupOrder)
            ->filter(fn($g) => $grouped->has($g))
            ->mapWithKeys(fn($g) => [$g => $grouped[$g]]);

        $sortKeys = [
            'By Decade'    => ['new-releases','2020s-picks','2010s-picks','2000s-gems','90s-nostalgia','80s-classics','70s-picks','60s-picks','50s-picks','classic-hollywood'],
            'Netflix'      => ['netflix-drama','netflix-comedy','netflix-thriller','netflix-action','netflix-romance','netflix-horror','netflix-scifi','netflix-docs','netflix-crime','netflix-mystery','netflix-fantasy','netflix-animation','netflix-adventure','netflix-family','netflix-history','netflix-war','netflix-western','netflix-anime'],
            'Prime Video'  => ['prime-action','prime-drama','prime-thrillers','prime-comedy','prime-horror','prime-scifi','prime-adventure','prime-romance','prime-documentary','prime-crime','prime-mystery','prime-fantasy','prime-family','prime-history','prime-animation','prime-war','prime-western'],
            'HBO'          => ['hbo-drama','hbo-thriller','hbo-crime','hbo-comedy','hbo-action','hbo-horror','hbo-scifi','hbo-romance','hbo-documentary','hbo-mystery','hbo-adventure','hbo-fantasy','hbo-history','hbo-family','hbo-war','hbo-animation','hbo-western'],
            'Disney+'      => ['disney-family','disney-action','disney-adventure','disney-animation','disney-fantasy','disney-comedy','disney-scifi','disney-drama','disney-romance','disney-mystery','disney-history','disney-documentary','disney-thriller','disney-crime','disney-war','disney-horror','disney-western'],
            'Apple TV+'    => ['apple-originals','apple-drama','apple-thriller','apple-scifi','apple-action','apple-comedy','apple-romance','apple-mystery','apple-documentary','apple-adventure','apple-crime','apple-fantasy','apple-history','apple-family','apple-animation','apple-war','apple-horror','apple-western'],
            'World Cinema' => ['korean-cinema','japanese-cinema','french-cinema','spanish-cinema','italian-cinema','chinese-cinema','bollywood','german-cinema','turkish-cinema','portuguese-cinema','lithuanian-cinema'],
            'Anime'        => ['anime','anime-action','anime-fantasy','anime-adventure','anime-drama','anime-scifi','anime-comedy','anime-romance','anime-horror','anime-thriller','anime-mystery','anime-crime','anime-family','anime-history','anime-war','anime-western','anime-documentary'],
            'By Genre'     => ['genre-action','genre-drama','genre-comedy','genre-thriller','genre-horror','genre-scifi','feel-good-romance','crime-heist','genre-mystery','genre-adventure','genre-animation','genre-documentary','genre-fantasy','genre-family','genre-history','genre-war','genre-western'],
        ];

        $grouped = $grouped->map(function ($items, $groupName) use ($sortKeys) {
            if (!isset($sortKeys[$groupName])) {
                return $items;
            }
            $order = array_flip($sortKeys[$groupName]);
            return $items->sortBy(fn($r) => $order[$r->slug] ?? 999)->values();
        });

        return view('roulettes', compact('grouped'));
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