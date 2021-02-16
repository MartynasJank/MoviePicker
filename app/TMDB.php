<?php

namespace App;

use App\Interfaces\ApiMovieInterface as ApiMovie;
use Illuminate\Database\Eloquent\Model;

class TMDB extends Model implements ApiMovie
{
    public function discover($input = [])
    {
        $smallYear = 1950;
        // Converting genres array to string for api request
        $input = $this->fixMovieArrayKeys($input);
        // SET UP DEFAULT VALUES AND CONVERT VALUES FOR API REQUEST FOR BETTER RESULT EXPERIENCE
        if (count($input) <= 1) {
            $input['with_original_language'] = 'en';
        }
        if (isset($input['with_genres'])) {
            $input['with_genres'] = implode(',', $input['with_genres']);
        }
        if (isset($input['without_genres'])) {
            $input['without_genres'] = implode(',', $input['without_genres']);
        }
        // Checks what values were submitted and adds default values for api request
        if (isset($input['primary_release_date.gte'])) {
            $input['primary_release_date.gte'] = $input['primary_release_date.gte'].'-01-01';
        }
        if (isset($input['primary_release_date.lte'])) {
            if($input['primary_release_date.lte'] < 1950){
                $smallYear = $input['primary_release_date.lte'];
            }
            $input['primary_release_date.lte'] = $input['primary_release_date.lte'].'-12-31';
        }
        // if none of dates is set give a default start year
        if(!isset($input['primary_release_date.gte']) && !isset($input['primary_release_date.lte'])){
            $input['primary_release_date.gte'] = '1970-01-01';
        }

        if (!isset($input['vote_count.gte'])) {
            $input['vote_count.gte'] = 10;
        }
        $input['sort_by'] = $this->randomSort();
        $input['with_runtime.gte'] = 40;
        $input['language'] = 'en-US';
        $input['include_adult'] = 'false';
        $input['include_video'] = 'false';

        if($smallYear < 1950){
            $input['vote_count.gte'] = 0;
            unset($input['with_runtime.gte']);
        }
        if($smallYear == 1874){
            unset($input['with_original_language']);
        }

        $url = 'https://api.themoviedb.org/3/discover/movie/?api_key='.config('api.TMDB').'&'.http_build_query($input);
        $json = @file_get_contents($url);
        if ($json == false) {
            abort(404);
        }
        $movies = json_decode($json);
        if ($movies->total_results === 0) {
            abort(404);
        }
        return $movies;
    }

    // Uses some information from movie() method to get total pages of similar movies to make results more random
    public function similarMovies($movieObj)
    {
        // Returns null if movie doesn't have any similar movies
        if ($movieObj->similar->total_results == 0) {
            return null;
        }

        $page = $movieObj->similar->total_pages > 20 ? rand(1, 20) : rand(1, $movieObj->similar->total_pages);

        $append = [
            'language' => 'en-US',
            'page' => $page
        ];

        $url = 'https://api.themoviedb.org/3/movie/'.$movieObj->id.'/similar?api_key='.config('api.TMDB').'&'.http_build_query($append);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result);
    }

    // Gets Detailed movie info
    public function movie($movieId)
    {
        $append = [
            'append_to_response' => 'videos,credits,similar'
        ];

        $url = 'https://api.themoviedb.org/3/movie/'.$movieId.'?api_key='.config('api.TMDB').'&'.http_build_query($append);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result);
    }

    public function trending()
    {
        $url = 'https://api.themoviedb.org/3/trending/movie/day?api_key='.config('api.TMDB');
        $json = file_get_contents($url);
        $movieObj = json_decode($json);
        return $movieObj;
    }

    // Gets all genres from TMDB api
    public function genres()
    {
        $url = 'https://api.themoviedb.org/3/genre/movie/list?api_key='.config('api.TMDB');
        $json = file_get_contents($url);
        $genresObj = json_decode($json);
        return $genresObj->genres;
    }

    // Replaces last _ with . for API request
    protected function fixMovieArrayKeys($data = [])
    {
        foreach ($data as $key => $value) {
            if (preg_match('/_(?=gte|lte)/', $key)) {
                $newKey = preg_replace('/_(?=gte|lte)/', '.', $key);
                $data[$newKey] = $data[$key];
                unset($data[$key]);
            }
        }
        return $data;
    }

    // Chooses random sort method for api to make results more random
    protected function randomSort()
    {
        $start = [
            'popularity',
            'release_date',
            'revenue',
            'primary_release_date',
            'original_title',
            'vote_average',
            'vote_count'
        ];

        $end = [
            '.asc',
            '.desc'
        ];

        return $start[array_rand($start)].$end[array_rand($end)];
    }
}
