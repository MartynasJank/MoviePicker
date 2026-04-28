<?php

namespace App;

use App\Interfaces\ApiMovieInterface as ApiMovie;
use Illuminate\Database\Eloquent\Model;

class OMDB extends Model implements ApiMovie
{
    public function movie($imdbId)
    {
        $apiData = [
            'apikey' => config('api.OMDB'),
            'i' => $imdbId,
            'tomatoes' => 'true',
        ];

        // Request OMDB api for more detailed movie information
        $url = 'https://private.omdbapi.com/?'.http_build_query($apiData);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result);
    }
}
