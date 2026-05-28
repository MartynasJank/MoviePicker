<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use App\Services\MovieService;
use Illuminate\View\View;

class CriteriaController extends Controller
{
    public function __invoke(TmdbClient $tmdb, MovieService $ms): View
    {
        $keywordIds       = (array) session('userInput.with_keywords',       []);
        $keywordNames     = (array) session('userInput.with_keywords_names', []);
        $selectedKeywords = array_map(null, $keywordIds, $keywordNames);

        return view('criteria', [
            'genres'           => $ms->genres($tmdb),
            'providersArray'   => $ms->buildProvidersArray($tmdb),
            'userInput'        => [],
            'selectedKeywords' => $selectedKeywords,
        ]);
    }
}
