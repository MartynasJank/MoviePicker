<?php

namespace App\Http\Controllers;

use App\Services\MovieService;
use App\Services\TmdbClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PersonController extends Controller
{
    public function __invoke(Request $request, TmdbClient $tmdb, MovieService $movieService): View
    {
        $id = (int) $request->route('id');

        try {
            $person = $tmdb->personDetail($id);
            if (empty($person->id)) abort(404);
        } catch (\Throwable) {
            abort(404);
        }

        $credits = collect($person->combined_credits->cast ?? [])
            ->merge(collect($person->combined_credits->crew ?? []))
            ->unique(fn($c) => ($c->media_type ?? '') . ($c->id ?? ''))
            ->sortByDesc('vote_count')
            ->values();

        $tvRollFilter    = $movieService->tvCreditFilter();
        $tvDisplayFilter = $movieService->tvCreditFilter(minEpisodes: 2);

        // 4 filmography tabs
        $movieCast = collect($person->combined_credits->cast ?? [])
            ->filter(fn($c) => ($c->media_type ?? '') === 'movie')
            ->sortByDesc('vote_count')
            ->values();

        $tvCast = collect($person->combined_credits->cast ?? [])
            ->filter(fn($c) => ($c->media_type ?? '') === 'tv')
            ->filter($tvDisplayFilter)
            ->sortByDesc('vote_count')
            ->values();

        $movieCrew = collect($person->combined_credits->crew ?? [])
            ->filter(fn($c) => ($c->media_type ?? '') === 'movie')
            ->unique('id')
            ->sortByDesc('vote_count')
            ->values();

        $tvCrew = collect($person->combined_credits->crew ?? [])
            ->filter(fn($c) => ($c->media_type ?? '') === 'tv')
            ->filter($tvDisplayFilter)
            ->unique('id')
            ->sortByDesc('vote_count')
            ->values();

        $hasMovieCast = $movieCast->isNotEmpty();
        $hasMovieCrew = $movieCrew->isNotEmpty();
        $hasTvCast    = $tvCast->isNotEmpty();
        $hasTvCrew    = $tvCrew->isNotEmpty();

        return view('person', compact('person', 'movieCast', 'tvCast', 'movieCrew', 'tvCrew', 'hasMovieCast', 'hasMovieCrew', 'hasTvCast', 'hasTvCrew'));
    }
}
