<?php

namespace App\Services;

use App\Interfaces\MovieApiInterface as ApiMovie;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;

class TmdbClient implements ApiMovie
{
    // Read-access token — not secret, already public in repo history.
    private const BEARER = 'eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiI0ZDg4NjhiNGMzOGM0YTk0MWYxNTU4NmQ4MjRjYjgwNiIsInN1YiI6IjVlOGFmYTZjYzRhZDU5MDAxM2ZmM2IyOCIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.SOJk4kMLahEcmJf9riMYfZL1pnb7YwuLWosSdFfNLwU';

    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'headers' => [
                'Authorization' => 'Bearer ' . self::BEARER,
                'Accept'        => 'application/json',
            ],
        ]);
    }

    /**
     * Discover movies by arbitrary filter criteria.
     *
     * @throws GuzzleException
     */
    public function discover(array $input = [], string $country = 'LT'): array
    {
        $input = $this->fixMovieArrayKeys($input);

        // Default language when no filters are set
        if (count($input) <= 1) {
            $input['with_original_language'] = 'en';
        }

        // Implode array values to comma-separated strings
        foreach (['with_genres', 'without_genres', 'with_watch_providers', 'with_cast', 'with_crew'] as $key) {
            if (isset($input[$key]) && is_array($input[$key])) {
                $input[$key] = implode(',', $input[$key]);
            }
        }

        // Date range formatting
        $smallYear = 1950;
        if (isset($input['primary_release_date.gte'])) {
            $input['primary_release_date.gte'] .= '-01-01';
        }
        if (isset($input['primary_release_date.lte'])) {
            if ($input['primary_release_date.lte'] < 1950) {
                $smallYear = (int) $input['primary_release_date.lte'];
            }
            $input['primary_release_date.lte'] .= '-12-31';
        }
        if (!isset($input['primary_release_date.gte']) && !isset($input['primary_release_date.lte'])) {
            $input['primary_release_date.gte'] = '1970-01-01';
        }

        $hasPeople = isset($input['with_cast']) || isset($input['with_crew']);

        $input['vote_count.gte']  ??= $hasPeople ? 0 : 10;
        $input['sort_by']         ??= $this->randomSort();
        $input['language']          = 'en-US';
        $input['include_adult']     = 'false';
        $input['include_video']     = 'false';
        if (!$hasPeople) {
            $input['with_runtime.gte'] = 40;
        }

        // Relax constraints for very old films
        if ($smallYear < 1950) {
            $input['vote_count.gte'] = 0;
            unset($input['with_runtime.gte']);
        }
        if ($smallYear == 1874) {
            unset($input['with_original_language']);
        }

        if (isset($input['with_watch_providers'])) {
            $input['watch_region'] = $country;
        }

        $url      = 'https://api.themoviedb.org/3/discover/movie?' . http_build_query($input);
        $response = $this->client->get($url);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Full movie details with videos, credits, similar, and watch/providers appended.
     * Cached per movie ID for 6 hours.
     */
    public function movie($movieId): object
    {
        return Cache::remember('tmdb_movie_' . $movieId, now()->addHours(6), function () use ($movieId) {
            $url = 'https://api.themoviedb.org/3/movie/' . $movieId . '?'
                . http_build_query(['append_to_response' => 'videos,credits,similar,watch/providers']);

            $response = $this->client->get($url);
            return json_decode($response->getBody()->getContents());
        });
    }

    /**
     * A random page of similar movies. Not cached — randomness is the point.
     *
     * @throws GuzzleException
     */
    public function similarMovies(object $movieObj): ?array
    {
        if ($movieObj->similar->total_results == 0) {
            return null;
        }

        $maxPage = min($movieObj->similar->total_pages, 20);
        $url     = 'https://api.themoviedb.org/3/movie/' . $movieObj->id . '/similar?'
            . http_build_query(['language' => 'en-US', 'page' => rand(1, $maxPage)]);

        $response = $this->client->get($url);
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Trending movies cached until TMDB next updates that list (midnight UTC for day, Monday midnight for week).
     */
    public function trending(string $period = 'day'): array
    {
        $period = $period === 'week' ? 'week' : 'day';
        $ttl = $period === 'day'
            ? now('UTC')->diffInSeconds(\Carbon\Carbon::tomorrow('UTC'))
            : now('UTC')->diffInSeconds(\Carbon\Carbon::now('UTC')->startOfWeek(\Carbon\Carbon::MONDAY)->addWeek());

        return Cache::remember("tmdb_trending_{$period}", $ttl, function () use ($period) {
            $response = $this->client->get("https://api.themoviedb.org/3/trending/movie/{$period}");
            return json_decode($response->getBody()->getContents(), true);
        });
    }

    public function trendingTv(string $period = 'day'): array
    {
        $period = $period === 'week' ? 'week' : 'day';
        $ttl = $period === 'day'
            ? now('UTC')->diffInSeconds(\Carbon\Carbon::tomorrow('UTC'))
            : now('UTC')->diffInSeconds(\Carbon\Carbon::now('UTC')->startOfWeek(\Carbon\Carbon::MONDAY)->addWeek());

        return Cache::remember("tmdb_trending_tv_{$period}", $ttl, function () use ($period) {
            $response = $this->client->get("https://api.themoviedb.org/3/trending/tv/{$period}");
            return json_decode($response->getBody()->getContents(), true);
        });
    }

    public function searchMovies(string $query): array
    {
        $url = 'https://api.themoviedb.org/3/search/movie?' . http_build_query([
            'language'      => 'en-US',
            'query'         => $query,
            'page'          => 1,
            'include_adult' => 'false',
        ]);
        return json_decode($this->client->get($url)->getBody()->getContents(), true);
    }

    public function searchPeople(string $query): array
    {
        $url = 'https://api.themoviedb.org/3/search/person?' . http_build_query([
            'language'      => 'en-US',
            'query'         => $query,
            'page'          => 1,
            'include_adult' => 'false',
        ]);
        return json_decode($this->client->get($url)->getBody()->getContents(), true);
    }

    public function person(int $id): object
    {
        return Cache::remember('tmdb_person_' . $id, now()->addHour(), function () use ($id) {
            $url = 'https://api.themoviedb.org/3/person/' . $id . '?' . http_build_query(['language' => 'en-US']);
            return json_decode($this->client->get($url)->getBody()->getContents());
        });
    }

    public function collection(int $id): object
    {
        return Cache::remember('tmdb_collection_' . $id, now()->addWeek(), function () use ($id) {
            $url = 'https://api.themoviedb.org/3/collection/' . $id . '?' . http_build_query(['language' => 'en-US']);
            return json_decode($this->client->get($url)->getBody()->getContents());
        });
    }

    /**
     * Genre list — callers should use MovieService::genres() which caches the result.
     */
    public function genres(): string
    {
        $url = 'https://api.themoviedb.org/3/genre/movie/list?' . http_build_query(['language' => 'en-US']);
        $response = $this->client->get($url);
        return $response->getBody()->getContents();
    }

    // ── TV Shows ─────────────────────────────────────────────────────────────

    /**
     * Discover TV shows by arbitrary filter criteria.
     *
     * @throws GuzzleException
     */
    public function discoverTv(array $input = [], string $country = 'LT'): array
    {
        $input = $this->fixMovieArrayKeys($input);

        if (count($input) <= 1) {
            $input['with_original_language'] = 'en';
        }

        foreach (['with_genres', 'without_genres', 'with_watch_providers', 'with_people'] as $key) {
            if (isset($input[$key]) && is_array($input[$key])) {
                $input[$key] = implode(',', $input[$key]);
            }
        }

        if (isset($input['first_air_date.gte'])) {
            $input['first_air_date.gte'] .= '-01-01';
        }
        if (isset($input['first_air_date.lte'])) {
            $input['first_air_date.lte'] .= '-12-31';
        }
        if (!isset($input['first_air_date.gte']) && !isset($input['first_air_date.lte'])) {
            $input['first_air_date.gte'] = '1990-01-01';
        }

        $hasPeople = isset($input['with_people']);

        $input['vote_count.gte']  ??= $hasPeople ? 0 : 10;
        $input['sort_by']         ??= $this->randomTvSort();
        $input['language']          = 'en-US';
        $input['include_adult']     = 'false';

        if (isset($input['with_watch_providers'])) {
            $input['watch_region'] = $country;
        }

        $url      = 'https://api.themoviedb.org/3/discover/tv?' . http_build_query($input);
        $response = $this->client->get($url);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Full TV show details with videos, credits, similar, and watch/providers appended.
     * Cached per show ID for 6 hours.
     */
    public function tvShow(int $id): object
    {
        return Cache::remember('tmdb_tv_' . $id, now()->addHours(6), function () use ($id) {
            $url = 'https://api.themoviedb.org/3/tv/' . $id . '?'
                . http_build_query(['append_to_response' => 'videos,credits,similar,watch/providers']);

            $response = $this->client->get($url);
            return json_decode($response->getBody()->getContents());
        });
    }

    /**
     * A random page of similar TV shows.
     *
     * @throws GuzzleException
     */
    public function similarShows(object $showObj): ?array
    {
        if ($showObj->similar->total_results == 0) {
            return null;
        }

        $maxPage = min($showObj->similar->total_pages, 20);
        $url     = 'https://api.themoviedb.org/3/tv/' . $showObj->id . '/similar?'
            . http_build_query(['language' => 'en-US', 'page' => rand(1, $maxPage)]);

        $response = $this->client->get($url);
        return json_decode($response->getBody()->getContents(), true);
    }

    /** TV genre list — use MovieService::tvGenres() which caches the result. */
    public function tvGenres(): string
    {
        $url = 'https://api.themoviedb.org/3/genre/tv/list?' . http_build_query(['language' => 'en-US']);
        return $this->client->get($url)->getBody()->getContents();
    }

    public function searchTv(string $query): array
    {
        $url = 'https://api.themoviedb.org/3/search/tv?' . http_build_query([
            'language'      => 'en-US',
            'query'         => $query,
            'page'          => 1,
            'include_adult' => 'false',
        ]);
        return json_decode($this->client->get($url)->getBody()->getContents(), true);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Rename form keys like `vote_count_gte` → `vote_count.gte` for the API. */
    protected function fixMovieArrayKeys(array $data): array
    {
        foreach ($data as $key => $value) {
            if (preg_match('/_(?=gte|lte)/', $key)) {
                $newKey        = preg_replace('/_(?=gte|lte)/', '.', $key);
                $data[$newKey] = $value;
                unset($data[$key]);
            }
        }
        return $data;
    }

    /** Random sort order so repeated requests return different results. */
    protected function randomSort(): string
    {
        $fields = ['popularity', 'release_date', 'revenue', 'primary_release_date',
                   'original_title', 'vote_average', 'vote_count'];
        return $fields[array_rand($fields)] . (rand(0, 1) ? '.asc' : '.desc');
    }

    protected function randomTvSort(): string
    {
        $fields = ['popularity', 'first_air_date', 'vote_average', 'vote_count', 'name'];
        return $fields[array_rand($fields)] . (rand(0, 1) ? '.asc' : '.desc');
    }
}
