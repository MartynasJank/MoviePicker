<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use App\Services\MovieService;
use Illuminate\View\View;

class CriteriaController extends Controller
{
    public function __invoke(TmdbClient $tmdb, MovieService $ms): View
    {
        session()->forget('userInput');

        return view('criteria', [
            'genres'         => $ms->genres($tmdb),
            'providersArray' => $ms->buildProvidersArray($tmdb),
        ]);
    }
}
