<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TvSeasonController extends Controller
{
    public function __invoke(Request $request, TmdbClient $tmdb): View
    {
        $showId       = (int) $request->route('id');
        $seasonNumber = (int) $request->route('season');

        try {
            $show   = $tmdb->tvShow($showId);
            $season = $tmdb->tvSeason($showId, $seasonNumber);
            if (empty($show->id) || empty($season->id)) abort(404);
        } catch (\Throwable) {
            abort(404);
        }

        return view('tv.season', compact('show', 'season', 'showId', 'seasonNumber'));
    }
}
