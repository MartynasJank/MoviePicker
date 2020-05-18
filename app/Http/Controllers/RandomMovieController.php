<?php

namespace App\Http\Controllers;

use App\OMDB;
use App\TMDB;
use Illuminate\Http\Request;
use App\Services\MovieService;
use App\Services\UrlGenerator;
use App\Http\Requests\CheckFormData;
use Symfony\Component\Console\Input\Input;

class RandomMovieController extends Controller
{
    public function show(CheckFormData $request, MovieService $movieService, TMDB $tmdb, OMDB $omdb, UrlGenerator $link)
    {
        if ($request->query('i') !== null && session('userInput') !== null) {
            session()->forget('userInput');
            return redirect(url('/movie'));
        }

        $movieCriteria = $request->except(['_token', 'flexdatalist-with_cast', 'flexdatalist-with_crew']);

        if (session('userInput') === null) {
            $request->session()->put('userInput', $movieCriteria);
        }
        if ($movieCriteria != session('userInput')) {
            $movieCriteria = session('userInput');
        }

        $allMovies = $tmdb->discover($movieCriteria);
        $movieCriteria['page'] = $movieService->randomPage($allMovies->total_pages);
        $randomMovieList = $tmdb->discover($movieCriteria);
        $randomMovie = $movieService->randomMovie($randomMovieList->results);

        return redirect()->route('movie', [$randomMovie->id]);
    }
}
