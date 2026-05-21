<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(TmdbClient $tmdb): View
    {
        $savedIds = auth()->check()
            ? auth()->user()->watchlist()->pluck('tmdb_id')->toArray()
            : [];

        return view('home', ['trending' => $tmdb->trending(), 'savedIds' => $savedIds]);
    }
}
