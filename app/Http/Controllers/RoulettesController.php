<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\TMDB;
use App\Services\MovieService;
use Illuminate\Support\Facades\Cookie;

class RoulettesController extends Controller
{
    public function show(){
        return view('roulettes');
    }

    // Netflix Horror
    public function netflixHorror(MovieService $movieService, TMDB $tmdb){
        $title = "Netflix Horror - MoviePicker";
        $tag = "Netflix Horror Movies";
        $criteria = [
            "with_genres" =>  [27],
            "with_watch_providers" =>  [8],
        ];
        $movieCriteria = $this->getMovieCriteria($movieService, $tmdb, 'netflixHorror', $criteria);
        $movies = $this->getMovies($tmdb, $movieCriteria);
        $movie_genres = $this->getGenres($tmdb, $movieService, $movies);
        $this->identifier();

        return view('multiple', compact('movies', 'movie_genres', 'tag', 'title'));
    }

    // Netflix Documentary
    public function netflixDoc(MovieService $movieService, TMDB $tmdb){
        $title = "Netflix Documentaries - MoviePicker";
        $tag = "Netflix Documenteries";
        $criteria = [
            "with_genres" =>  [99],
            "with_watch_providers" =>  [8],
        ];
        $movieCriteria = $this->getMovieCriteria($movieService, $tmdb, 'netflixHorror', $criteria);
        $movies = $this->getMovies($tmdb, $movieCriteria);
        $movie_genres = $this->getGenres($tmdb, $movieService, $movies);
        $this->identifier();

        return view('multiple', compact('movies', 'movie_genres', 'tag', 'title'));
    }

    // Netflix Anime Movies
    public function netflixAnimeMovies(MovieService $movieService, TMDB $tmdb){
        $title = "Netflix Anime Movies - MoviePicker";
        $tag = "Netflix Anime Movies";
        $criteria = [
            "with_genres" =>  [16],
            "with_watch_providers" =>  [8],
            "with_original_language" => 'ja'
        ];
        $movieCriteria = $this->getMovieCriteria($movieService, $tmdb, 'netflixHorror', $criteria);
        $movies = $this->getMovies($tmdb, $movieCriteria);
        $movie_genres = $this->getGenres($tmdb, $movieService, $movies);
        $this->identifier();

        return view('multiple', compact('movies', 'movie_genres', 'tag', 'title'));
    }

    // MOVE TO ROULETTE SERVICE
    ////////////////////
    // TECHNICAL STUFF//
    ////////////////////
    private function getMovieCriteria(MovieService $movieService, TMDB $tmdb, $type, $criteria){
        if(session('roulette.type') != $type && session()->has('roulette')){
            session()->forget('roulette');
        }

        $criteria['type'] = $type;
        session()->put('roulette', $criteria);

        if (session()->has('roulette.total_pages')) {
            $criteria['page'] = $movieService->randomPage(session()->get('roulette.total_pages'));
        } else {
            $allMovies = $tmdb->discover($criteria);
            session()->put('roulette.total_pages', $allMovies->total_pages);
            $criteria['page'] = $movieService->randomPage($allMovies->total_pages);
        }

        return $criteria;
    }

    private function getMovies(TMDB $tmdb, $criteria){
        $movies = $tmdb->discover($criteria);

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
        return $movies;
    }

    private function getGenres(TMDB $tmdb, MovieService $movieService, $movies){
        $all_genres = $movieService->genres($tmdb);
        $genres_array = [];
        foreach ($all_genres as $genre){
            $genres_array[$genre->name] = $genre->id;
        }

        // Movie genres from ids to string
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
        return $movie_genres;
    }

    private function identifier(){
        $randomHash = md5(uniqid(rand(), true));
        if (Cookie::get('visitor') === null) {
            Cookie::queue(Cookie::make('visitor', $randomHash, 525600));
        }
    }
}
