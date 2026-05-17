<?php

namespace App\Http\Controllers;

use App\TMDB;

class HomeController extends Controller
{
    public function index(TMDB $tmdb)
    {
        return view('home', ['trending' => $tmdb->trending()]);
    }
}
