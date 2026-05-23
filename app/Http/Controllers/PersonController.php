<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PersonController extends Controller
{
    public function __invoke(Request $request, TmdbClient $tmdb): View
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

        // Known for: top 8 by popularity, deduplicated by title ID
        $knownFor = collect($person->combined_credits->cast ?? [])
            ->sortByDesc('popularity')
            ->unique('id')
            ->take(8)
            ->values();

        // Full filmography split by type, sorted by date desc
        $movies = $credits->filter(fn($c) => ($c->media_type ?? '') === 'movie')
            ->sortByDesc(fn($c) => $c->release_date ?? '')
            ->values();

        $tvShows = $credits->filter(fn($c) => ($c->media_type ?? '') === 'tv')
            ->sortByDesc(fn($c) => $c->first_air_date ?? '')
            ->values();

        return view('person', compact('person', 'knownFor', 'movies', 'tvShows'));
    }
}
