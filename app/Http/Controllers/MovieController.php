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

        if(isset($tmdbInfo->{"watch/providers"}->results->{$movieService->getUserCountry()}->flatrate)){
            $watchProviders = $tmdbInfo->{"watch/providers"}->results->{$movieService->getUserCountry()};

        } else {
            $watchProviders = null;
        }

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

        // GETS TRAILER OF THE MOVIE
        $trailer = $movieService->getTrailer($tmdbInfo->videos->results);

        // USER INPUT TO ADJUST FORM
        $all_genres = $movieService->genres($tmdb);
        $user_input = $request->session()->get('userInput', 'default');

        // SAVES INFO FOR STATS WEBSITE
        $click->input = json_encode(session('userInput'));
        $click->visitor = Cookie::get('visitor') ?? $randomHash;
        $click->result = $movieId;
        $click->save();
        return view('movie', compact( 'tmdbInfo', 'omdbInfo', 'urls', 'similarMovies', 'genres', 'trailer', 'user_input', 'all_genres', 'watchProviders', 'providersArray'));
    }
}

