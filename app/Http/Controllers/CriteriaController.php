<?php

namespace App\Http\Controllers;

use App\TMDB;
use App\Services\MovieService;
use Illuminate\Http\Request;

class CriteriaController extends Controller
{
    public function index(Tmdb $tmdb, MovieService $ms)
    {
        $movieProvider = $ms->getWatchProviders();
        $providersArray = array();
        foreach ($movieProvider->results as $key => $provider){
            $providersArray[] = array(
                'id' => $provider->provider_id,
                'name' => $provider->provider_name,
                'logo' => 'https://image.tmdb.org/t/p/w45'.$provider->logo_path
            );
        }
        if (session('userInput') !== null) {
            session()->forget('userInput');
        }
        $genres = $ms->genres($tmdb);

        return view('criteria', compact('genres', 'providersArray'));
    }
}
