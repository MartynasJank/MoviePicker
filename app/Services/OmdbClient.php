<?php

namespace App\Services;

use App\Interfaces\ApiMovieInterface as ApiMovie;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;

class OmdbClient implements ApiMovie
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * Fetch full movie details from OMDb including Rotten Tomatoes data.
     * Cached per IMDb ID for 6 hours.
     *
     * @throws GuzzleException
     */
    public function movie($imdbId): ?object
    {
        if (empty($imdbId)) {
            return null;
        }

        return Cache::remember('omdb_movie_' . $imdbId, now()->addHours(6), function () use ($imdbId) {
            $url      = 'https://private.omdbapi.com/?' . http_build_query([
                'apikey'   => config('api.OMDB'),
                'i'        => $imdbId,
                'tomatoes' => 'true',
            ]);
            $response = $this->client->get($url);
            $data = json_decode($response->getBody()->getContents());
            return (isset($data->Response) && $data->Response === 'False') ? null : $data;
        });
    }
}
