<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use App\Services\MovieService;

class CriteriaController extends Controller
{
    public function index(TmdbClient $tmdb, MovieService $ms)
    {
        session()->forget('userInput');

        return view('criteria', [
            'genres'         => $ms->genres($tmdb),
            'providersArray' => $ms->buildProvidersArray($tmdb),
        ]);
    }
}
