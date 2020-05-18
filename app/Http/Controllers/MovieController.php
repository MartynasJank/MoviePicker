<?php

namespace App\Http\Controllers;

use App\OMDB;
use App\TMDB;
use App\Click;
use Illuminate\Http\Request;
use App\Services\MovieService;
use App\Services\UrlGenerator;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\Console\Input\Input;

class MovieController extends Controller
{
    public function show(Click $click, Request $request, TMDB $tmdb, OMDB $omdb, MovieService $movieService, UrlGenerator $link)
    {
        $randomHash = md5(uniqid(rand(), true));
        if (Cookie::get('visitor') === null) {
            Cookie::queue(Cookie::make('visitor', $randomHash, 525600));
        }

        $movieId = $request['id'];

        $tmdbInfo = $tmdb->movie($movieId);
        $omdbInfo = $omdb->movie($tmdbInfo->imdb_id);

        $similarMovies = $tmdb->similarMovies($tmdbInfo);

        $genres = $movieService->genresString($tmdbInfo);
        $urls = $link->linksArray($omdbInfo);

        $trailer = $movieService->getTrailer($tmdbInfo->videos->results);

        $click->input = json_encode(session('userInput'));
        $click->visitor = Cookie::get('visitor') ?? $randomHash;
        $click->result = $movieId;
        $click->save();

        return view('movie', compact('omdbInfo', 'tmdbInfo', 'urls', 'similarMovies', 'genres', 'trailer'));
    }

}

