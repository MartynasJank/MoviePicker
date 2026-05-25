<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use App\Services\MovieService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PersonRollController extends Controller
{
    public function movie(Request $request, int $id, TmdbClient $tmdb, MovieService $movieService): RedirectResponse
    {
        $country  = $movieService->getUserCountry();
        $type     = $request->query('type', 'cast');
        $criteria = $type === 'crew' ? ['with_crew' => [$id]] : ['with_cast' => [$id]];

        session(['userInput' => $criteria + ['vote_count_gte' => 10]]);

        $criteria['page'] = $movieService->resolvePage($tmdb, $criteria, $country);
        $results = $tmdb->discover($criteria, $country);

        return redirect()->route('movie', [$movieService->randomMovie($results['results'])['id']]);
    }

    public function tv(Request $request, int $id, TmdbClient $tmdb, MovieService $movieService): RedirectResponse
    {
        $person = $tmdb->personDetail($id);
        $type   = $request->query('type', 'cast');

        $pool = $type === 'crew'
            ? collect($person->combined_credits->crew ?? [])
            : collect($person->combined_credits->cast ?? []);

        $shows = $pool
            ->filter($movieService->tvCreditFilter())
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
            return redirect()->route('tv.pick');
        }

        return redirect()->route('tv.show', [$ids[array_rand($ids)]]);
    }

}
