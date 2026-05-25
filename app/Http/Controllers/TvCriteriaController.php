<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use App\Services\MovieService;
use Illuminate\View\View;

class TvCriteriaController extends Controller
{
    public function __invoke(TmdbClient $tmdb, MovieService $ms): View
    {
        return view('tv.criteria', [
            'genres'         => $ms->tvGenres($tmdb),
            'providersArray' => $ms->buildProvidersArray($tmdb),
            'userInput'      => [],
        ]);
    }
}
