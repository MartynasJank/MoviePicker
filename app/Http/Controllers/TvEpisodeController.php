<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TvEpisodeController extends Controller
{
    public function __invoke(Request $request, TmdbClient $tmdb): View
    {
        $showId        = (int) $request->route('id');
        $seasonNumber  = (int) $request->route('season');
        $episodeNumber = (int) $request->route('episode');

        try {
            $show    = $tmdb->tvShow($showId);
            $season  = $tmdb->tvSeason($showId, $seasonNumber);
            $episode = $tmdb->tvEpisode($showId, $seasonNumber, $episodeNumber);
            if (empty($show->id) || empty($episode->id)) abort(404);
        } catch (\Throwable) {
            abort(404);
        }

        $episodes = collect($season->episodes ?? []);
        $prevEpisode = $episodes->firstWhere('episode_number', $episodeNumber - 1);
        $nextEpisode = $episodes->firstWhere('episode_number', $episodeNumber + 1);

        return view('tv.episode', compact(
            'show', 'season', 'episode',
            'showId', 'seasonNumber', 'episodeNumber',
            'prevEpisode', 'nextEpisode'
        ));
    }
}
