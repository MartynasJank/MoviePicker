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

        // Known for: top 8 by vote_count, deduplicated by title ID
        $knownFor = collect($person->combined_credits->cast ?? [])
            ->sortByDesc('vote_count')
            ->unique('id')
            ->take(8)
            ->values();

        // Movies: all credits sorted by vote_count (most notable first)
        $movies = $credits->filter(fn($c) => ($c->media_type ?? '') === 'movie')
            ->sortByDesc('vote_count')
            ->values();

        // TV: filter out true one-offs/talk shows, sort by vote_count
        $tvShows = $credits->filter(fn($c) => ($c->media_type ?? '') === 'tv')
            ->filter($tvDisplayFilter)
            ->sortByDesc('vote_count')
            ->values();

        $hasMovieCast = collect($person->combined_credits->cast ?? [])
            ->contains(fn($c) => ($c->media_type ?? '') === 'movie');
        $hasMovieCrew = collect($person->combined_credits->crew ?? [])
            ->contains(fn($c) => ($c->media_type ?? '') === 'movie');

        $hasTvCast = collect($person->combined_credits->cast ?? [])->contains($tvRollFilter);
        $hasTvCrew = collect($person->combined_credits->crew ?? [])->contains($tvRollFilter);

        return view('person', compact('person', 'knownFor', 'movies', 'tvShows', 'hasMovieCast', 'hasMovieCrew', 'hasTvCast', 'hasTvCrew'));
    }
}
