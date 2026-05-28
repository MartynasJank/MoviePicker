<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use App\Services\MovieService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PersonRollController extends Controller
{
    public function movie(Request $request, int $id, TmdbClient $tmdb, MovieService $movieService): RedirectResponse
    {
        $country  = $movieService->getUserCountry();
        $type     = $request->query('type', 'cast');
        $criteria = $type === 'crew' ? ['with_crew' => [$id]] : ['with_cast' => [$id]];

        session(['userInput' => $criteria + ['vote_count_gte' => 10], 'roll_source' => 'other']);

        $criteria['page'] = $movieService->resolvePage($tmdb, $criteria, $country);
        $results = $tmdb->discover($criteria, $country);

        if (empty($results['results'])) {
            $criteria['page'] = $movieService->randomPage(session('userInput.total_pages', 500), $criteria['page']);
            $results = $tmdb->discover($criteria, $country);
        }

        if (empty($results['results'])) {
            return redirect()->route('person', $id);
        }

        return redirect()->route('movie', [$movieService->pickRandom($results['results'])['id']]);
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

        session([
            'tvPersonRollIds' => $shows->pluck('id')->map(fn($i) => (int)$i)->toArray(),
            'tvInput' => [
                $personKey              => [$id],
                $personKey . '_names'   => [(string)($person->name ?? '')],
                'vote_count_gte'        => 10,
            ],
            'roll_source' => 'other',
        ]);

        return redirect()->route('tv.show', [$shows->random()->id]);
    }

    public function movieRollJson(Request $request, int $id, TmdbClient $tmdb, MovieService $movieService): JsonResponse
    {
        $country  = $movieService->getUserCountry();
        $type     = $request->query('type', 'cast');
        $criteria = $type === 'crew' ? ['with_crew' => [$id]] : ['with_cast' => [$id]];

        session(['userInput' => $criteria + ['vote_count_gte' => 10], 'roll_source' => 'other']);
        session()->forget('batchUrl');

        $criteria['page'] = $movieService->resolvePage($tmdb, $criteria, $country);
        $results = $tmdb->discover($criteria, $country);

        $picked = $movieService->pickBatch($results['results'] ?? []);

        return response()->json($this->toRollCards($picked));
    }

    public function tvRollJson(Request $request, int $id, TmdbClient $tmdb, MovieService $movieService): JsonResponse
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
            return response()->json([]);
        }

        $personKey = $type === 'crew' ? 'with_crew' : 'with_cast';
        session([
            'tvPersonRollIds' => $shows->pluck('id')->map(fn($i) => (int)$i)->toArray(),
            'tvInput' => [
                $personKey            => [$id],
                $personKey . '_names' => [(string)($person->name ?? '')],
                'vote_count_gte'      => 10,
            ],
            'roll_source' => 'other',
        ]);
        session()->forget('batchUrl');

        $picked = $shows->shuffle()->take(12)->map(fn($m) => (array) $m)->values()->all();

        return response()->json($this->toRollCards($picked, 'tv'));
    }

    public function nextTvRoll(): RedirectResponse
    {
        $ids = session('tvPersonRollIds');

        if (empty($ids)) {
            return redirect()->route('tv.pick');
        }

        return redirect()->route('tv.show', [$ids[array_rand($ids)]]);
    }
}
