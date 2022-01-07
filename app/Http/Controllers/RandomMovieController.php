<?php

namespace App\Http\Controllers;

use App\OMDB;
use App\TMDB;
use App\Click;
use Illuminate\Http\Request;
use App\Services\MovieService;
use App\Services\UrlGenerator;
use App\Http\Requests\CheckFormData;
use Illuminate\Support\Facades\Cookie;
use PhpParser\Node\Stmt\ClassLike;
use Symfony\Component\Console\Input\Input;

class RandomMovieController extends Controller
{
    // returns one result
    public function show(CheckFormData $request, MovieService $movieService, TMDB $tmdb)
    {
        if ($request->query('i') !== null && session('userInput') !== null) {
            session()->forget('userInput');
            return redirect(url('/movie'));
        }

        if ($request->query('a') !== null) {
            session()->forget('userInput');
        }

        if($request->input('movie_search')){
            session()->forget('userInput');
            $id = $request->input('movie_search');
            return redirect(url('/movie/'.$id));
        }

        $movieCriteria = $this->movieCriteria($request);

        // Does one less API request if we know that total pages will be 500
        if (empty($movieCriteria)) {
            $movieCriteria['page'] = $movieService->randomPage(500);
        } else if (isset($movieCriteria['total_pages'])) {
            $movieCriteria['page'] = $movieService->randomPage($movieCriteria['total_pages']);
        } else {
            $allMovies = $tmdb->discover($movieCriteria);
            $request->session()->put('userInput.total_pages', $allMovies->total_pages);
            $movieCriteria['page'] = $movieService->randomPage($allMovies->total_pages);
        }

        $randomMovieList = $tmdb->discover($movieCriteria);
        $randomMovie = $movieService->randomMovie($randomMovieList->results);

        return redirect()->route('movie', [$randomMovie->id]);
    }

    // return multiple results
    public function multiple(CheckFormData $request, MovieService $movieService, TMDB $tmdb, Click $click)
    {
        $tag = "Movies picked for you";

        if ($request->query('i') !== null && session('userInput') !== null) {
            session()->forget('userInput');
            return redirect(url('/multiple'));
        }

        if ($request->query('a') !== null) {
            session()->forget('userInput');
        }

        $movieCriteria = $this->movieCriteria($request);
        if (empty($movieCriteria)) {
            $movieCriteria['page'] = $movieService->randomPage(500);
        } else if (isset($movieCriteria['total_pages'])) {
            $movieCriteria['page'] = $movieService->randomPage($movieCriteria['total_pages']);
        } else {
            $allMovies = $tmdb->discover($movieCriteria);
            $request->session()->put('userInput.total_pages', $allMovies->total_pages);
            $movieCriteria['page'] = $movieService->randomPage($allMovies->total_pages);
        }

        $movies = $tmdb->discover($movieCriteria);
        // returns maximum of 4 movies on screen randomly selected from 20
        $numbersArray = [];
        $movieCount = 4;
        if(count($movies->results) > $movieCount){
            // gets array of indexed
            for ($i = 0; $i < count($movies->results); $i++){
                $numbersArray[] = $i;
            }
            $j = 0;
            while($j < $movieCount){
                $random = rand(0, count($movies->results));
                if(in_array($random, $numbersArray)){
                    unset($numbersArray[$random]);
                    $j++;
                }
            }
            $movies->results = array_diff_key($movies->results, $numbersArray);
        }

        $testInfo = [];
        $testInfo['total'] = $movieCriteria['total_pages'] ?? $movies->total_pages;
        $testInfo['current'] = $movieCriteria['page'];

        // Gets all genres and converts them to workable array
        $all_genres = $movieService->genres($tmdb);
        $genres_array = [];
        foreach ($all_genres as $genre){
            $genres_array[$genre->name] = $genre->id;
        }

        // Assigns string genres to movies
        $movie_genres = [];
        foreach ($movies->results as $movie) {
            $genre_count = 0;
            foreach ($movie->genre_ids as $movie_genre) {
                if(in_array($movie_genre, $genres_array)){
                    $movie_genres[$movie->id][$genre_count] = array_keys($genres_array, $movie_genre)[0];
                    $genre_count++;
                }
            }
            if(isset($movie_genres[$movie->id])){
                $movie_genres[$movie->id] = implode(', ', $movie_genres[$movie->id]);
            }
        }
        $user_input = \Session::get('userInput');

        // FOR FORM
        $movieProvider = $movieService->getWatchProviders();
        $providersArray = array();
        foreach ($movieProvider->results as $key => $provider){
            $providersArray[] = array(
                'id' => $provider->provider_id,
                'name' => $provider->provider_name,
                'logo' => 'https://image.tmdb.org/t/p/w45'.$provider->logo_path
            );
        }

        $randomHash = md5(uniqid(rand(), true));
        if (Cookie::get('visitor') === null) {
            Cookie::queue(Cookie::make('visitor', $randomHash, 525600));
        }
        // SAVES INFO FOR STATS WEBSITE
//        $click->input = json_encode(session('userInput'));
//        $click->visitor = Cookie::get('visitor') ?? $randomHash;
//        $click->result = 'Multiple';
//        $click->save();


        return view('multiple', compact('movies', 'user_input', 'all_genres', 'movie_genres', 'testInfo', 'providersArray', 'tag'));
    }

    // Return movie criteria
    protected function movieCriteria(CheckFormData $request){
        $movieCriteria = $request->except(['_token', 'flexdatalist-with_cast', 'flexdatalist-with_crew', 'i', 'total_pages']);

        if (session('userInput') === null) {
            $request->session()->put('userInput', $movieCriteria);
        }

        if ($movieCriteria != session('userInput')) {
            $movieCriteria = session('userInput');
        }

        return $movieCriteria;
    }
}
