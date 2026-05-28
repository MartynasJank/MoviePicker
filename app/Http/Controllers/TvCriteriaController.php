<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use App\Services\MovieService;
use Illuminate\View\View;

class TvCriteriaController extends Controller
{
    public function __invoke(TmdbClient $tmdb, MovieService $ms): View
    {
        $keywordIds       = (array) session('tvInput.with_keywords',       []);
        $keywordNames     = (array) session('tvInput.with_keywords_names', []);
        $selectedKeywords = array_map(null, $keywordIds, $keywordNames);

        return view('tv.criteria', [
            'genres'           => $ms->genres($tmdb, 'tv'),
            'providersArray'   => $ms->buildProvidersArray($tmdb),
            'userInput'        => [],
            'selectedKeywords' => $selectedKeywords,
        ]);
    }
}
