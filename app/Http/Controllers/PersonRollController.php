<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use App\Services\MovieService;
use Illuminate\Http\RedirectResponse;

class PersonRollController extends Controller
{
    public function movie(int $id, TmdbClient $tmdb, MovieService $movieService): RedirectResponse
    {
        $country  = $movieService->getUserCountry();
        $type     = request('type', 'cast');
        $criteria = $type === 'crew' ? ['with_crew' => [$id]] : ['with_cast' => [$id]];

        session(['userInput' => $criteria + ['vote_count_gte' => 10]]);

        $criteria['page'] = $movieService->resolvePage($tmdb, $criteria, $country);
        $results = $tmdb->discover($criteria, $country);

        return redirect()->route('movie', [$movieService->randomMovie($results['results'])['id']]);
    }

    public function tv(int $id, TmdbClient $tmdb): RedirectResponse
    {
        $person = $tmdb->personDetail($id);
        $type   = request('type', 'cast');

        $pool = $type === 'crew'
            ? collect($person->combined_credits->crew ?? [])
            : collect($person->combined_credits->cast ?? []);

        $nonScriptedGenres = [10767, 10763, 10764];

        $shows = $pool
            ->filter(fn($c) => ($c->media_type ?? '') === 'tv'
                && !empty($c->id)
                && ($c->vote_count ?? 0) >= 10
                && ($c->episode_count ?? 0) >= 5
                && empty(array_intersect((array)($c->genre_ids ?? []), $nonScriptedGenres)))
            ->unique('id')
            ->values();

        if ($shows->isEmpty()) {
            return redirect()->route('person', $id);
        }

        $personKey = $type === 'crew' ? 'with_crew' : 'with_cast';

        session()->put('_debug', ['count' => $shows->count(), 'type' => 'TV shows (person ' . $type . ')', 'url' => null]);

        session([
            'tvPersonRollIds' => $shows->pluck('id')->map(fn($i) => (int)$i)->toArray(),
            'tvInput' => [
                $personKey              => [$id],
                $personKey . '_names'   => [(string)($person->name ?? '')],
                'vote_count_gte'        => 10,
            ],
        ]);

        return redirect()->route('tv.show', [$shows->random()->id]);
    }

    public function tvNext(): RedirectResponse
    {
        $ids = session('tvPersonRollIds');

        if (empty($ids)) {
            return redirect('/tv/pick');
        }

        return redirect()->route('tv.show', [$ids[array_rand($ids)]]);
    }
}
