<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use App\Services\MovieService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NoResultsController extends Controller
{
    public function __invoke(Request $request, TmdbClient $tmdb, MovieService $movieService): View
    {
        $type       = $request->query('type', 'movie');
        $isTv       = $type === 'tv';
        $user_input = session($isTv ? 'tvInput' : 'userInput', []);

        return view('no-results', [
            'isTv'           => $isTv,
            'randomUrl'      => $isTv ? url('/tv/pick?i=new') : url('/movie?i=new'),
            'user_input'     => $user_input,
            'all_genres'     => $movieService->genres($tmdb, $isTv ? 'tv' : 'movie'),
            'providersArray' => $movieService->buildProvidersArray($tmdb),
        ]);
    }
}
