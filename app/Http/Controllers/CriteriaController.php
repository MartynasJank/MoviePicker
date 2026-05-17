<?php

namespace App\Http\Controllers;

use App\TMDB;
use App\Services\MovieService;

class CriteriaController extends Controller
{
    public function index(TMDB $tmdb, MovieService $ms)
    {
        session()->forget('userInput');

        return view('criteria', [
            'genres'         => $ms->genres($tmdb),
            'providersArray' => $ms->buildProvidersArray($tmdb),
        ]);
    }
}
