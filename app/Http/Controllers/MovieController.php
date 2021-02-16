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
        // GENERATES HASH TO RECOGNIZE SAME USER
        $randomHash = md5(uniqid(rand(), true));
        if (Cookie::get('visitor') === null) {
            Cookie::queue(Cookie::make('visitor', $randomHash, 525600));
        }

        // MOVIE ID FOR URL
        $movieId = $request['id'];
        $movieCriteria = session('userInput');

        // API INFO
        $tmdbInfo = $tmdb->movie($movieId);
        $omdbInfo = $omdb->movie($tmdbInfo->imdb_id);

        // MOVIE RECOMMENDATIONS

        if(!empty($movieCriteria)){
            $movieCriteria['page'] = rand(1, $movieCriteria['total_pages']);
            $similarMovies = $tmdb->discover($movieCriteria);
            $similarMovies->type = 'discover';
            $similarMovies = (object) array_merge( (array)$similarMovies, array( 'type' => 'discover' ) );
            if(count($similarMovies->results) < 4){
                $similarMovies = $tmdb->similarMovies($tmdbInfo);
                $similarMovies->type = 'similar';
                $similarMovies = (object) array_merge( (array)$similarMovies, array( 'type' => 'similar' ) );

            }
        } else {
            $similarMovies = $tmdb->similarMovies($tmdbInfo);
            if($similarMovies != null) {
                $similarMovies = (object)array_merge((array)$similarMovies, array('type' => 'similar'));
            }
        }

        // GETS MOVIE GENRES
        $genres = $movieService->genresString($tmdbInfo);

        // GETS  URL TO MOVIE REVIEW WEBSTIES
        $urls = $link->linksArray($omdbInfo);

        // CLEAN TITLE
        $dirty_title = $tmdbInfo->title ?? $omdbInfo->Title;

        // BIG TESTING HERE
        $linksToStreams = $movieService->linksToStreams($dirty_title, $tmdbInfo->id);

        // GETS TRAILER OF THE MOVIE
        $trailer = $movieService->getTrailer($tmdbInfo->videos->results);

        // USER INPUT TO ADJUST FORM
        $all_genres = $tmdb->genres();
        $user_input = $request->session()->get('userInput', 'default');

        // SAVES INFO FOR STATS WEBSITE
        $click->input = json_encode(session('userInput'));
        $click->visitor = Cookie::get('visitor') ?? $randomHash;
        $click->result = $movieId;
        $click->save();

        return view('movie', compact('omdbInfo', 'tmdbInfo', 'urls', 'similarMovies', 'genres', 'trailer', 'user_input', 'all_genres', 'linksToStreams'));
    }
}

