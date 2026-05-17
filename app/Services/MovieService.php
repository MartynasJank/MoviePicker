<?php

namespace App\Services;

use App\Services\TmdbClient;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class MovieService
{
    public function randomPage(int $totalPages): int
    {
        return rand(1, min($totalPages, 500));
    }

    public function randomMovie(array $movieArray): array
    {
        return $movieArray[array_rand($movieArray)];
    }

    /** Genre list, cached for one week. */
    public function genres(TmdbClient $tmdb): array
    {
        return Cache::remember('tmdb_genres', now()->addWeek(), function () use ($tmdb) {
            return json_decode($tmdb->genres())->genres;
        });
    }

    public function genresString(object $movieObj): string
    {
        if (empty($movieObj->genres)) {
            return 'No Info';
        }

        return implode(', ', array_column((array) $movieObj->genres, 'name'));
    }

    /** Map discover results to a movieId => 'Genre1, Genre2' string lookup. */
    public function movieGenresMap(array $results, array $allGenres): array
    {
        $idToName = [];
        foreach ($allGenres as $genre) {
            $idToName[$genre->id] = $genre->name;
        }

        $map = [];
        foreach ($results as $movie) {
            $names = array_filter(array_map(fn($id) => $idToName[$id] ?? null, $movie['genre_ids']));
            if ($names) {
                $map[$movie['id']] = implode(', ', $names);
            }
        }
        return $map;
    }

    /**
     * Find the best available trailer key from a list of video objects.
     * All YouTube status checks are batched into a single API call.
     */
    public function getTrailer(array $videos): ?string
    {
        if (empty($videos) || empty(config('api.YOUTUBE'))) {
            return null;
        }

        $trailers = [];
        foreach ($videos as $video) {
            if ($video->type === 'Trailer') {
                $trailers[] = ['key' => $video->key, 'size' => $video->size];
            }
        }

        if (empty($trailers)) {
            return null;
        }

        usort($trailers, fn($a, $b) => $b['size'] <=> $a['size']);

        $ids = implode(',', array_column($trailers, 'key'));
        $url = 'https://www.googleapis.com/youtube/v3/videos?part=contentDetails,status&id='
             . $ids . '&key=' . config('api.YOUTUBE');

        try {
            $json = (new Client())->get($url)->getBody()->getContents();
        } catch (\Throwable) {
            return $trailers[0]['key'] ?? null;
        }

        $videoMap = [];
        foreach (json_decode($json)->items ?? [] as $item) {
            $videoMap[$item->id] = $item;
        }

        $country = $this->getUserCountry();

        foreach ($trailers as $trailer) {
            $item    = $videoMap[$trailer['key']] ?? null;
            $allowed = $item->contentDetails->regionRestriction->allowed ?? null;

            if (!$item) {
                continue;
            }

            if ($allowed !== null && !in_array($country, $allowed)) {
                continue;
            }

            return $trailer['key'];
        }

        return null;
    }

    /**
     * Full watch-provider list for the user's region, cached per country for one week.
     */
    public function getWatchProviders(): object
    {
        $country = $this->getUserCountry();

        return Cache::remember('tmdb_providers_' . $country, now()->addWeek(), function () use ($country) {
            $url = 'https://api.themoviedb.org/3/watch/providers/movie?'
                 . http_build_query(['api_key' => config('api.TMDB'), 'language' => 'en-US', 'watch_region' => $country]);

            return json_decode((new Client())->get($url)->getBody()->getContents());
        });
    }

    public function getUserCountry(): string
    {
        try {
            $data = \Location::get(\Request::ip());
        } catch (\Throwable) {
            $data = null;
        }

        return $data->countryCode ?? 'LT';
    }

    /** Build the $providersArray shape expected by every view that shows the form. */
    public function buildProvidersArray(TmdbClient $tmdb): array
    {
        $providers = $this->getWatchProviders();
        $result    = [];

        foreach ($providers->results as $provider) {
            $result[] = [
                'id'   => $provider->provider_id,
                'name' => $provider->provider_name,
                'logo' => 'https://image.tmdb.org/t/p/w45' . $provider->logo_path,
            ];
        }

        return $result;
    }
}
