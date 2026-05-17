<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;

class HomeController extends Controller
{
    public function index(TmdbClient $tmdb)
    {
        return view('home', ['trending' => $tmdb->trending()]);
    }
}
