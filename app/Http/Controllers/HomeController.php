<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(TmdbClient $tmdb): View
    {
        return view('home', ['trending' => $tmdb->trending()]);
    }
}
