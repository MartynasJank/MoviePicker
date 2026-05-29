<?php

namespace App\Services;

use App\Interfaces\MovieApiInterface as ApiMovie;
use App\Models\TmdbRequestLog;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
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
        foreach (['with_genres', 'without_genres', 'with_watch_providers', 'with_cast', 'with_crew', 'with_keywords'] as $key) {
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

        $url = 'https://api.themoviedb.org/3/discover/movie?' . http_build_query($input);
        return json_decode($this->fetch($url, 'discover_movie'), true);
    }

    /**
     * Full movie details with videos, credits, similar, and watch/providers appended.
     * Cached per movie ID for 6 hours.
     */
    public function movie($movieId): object
    {
        return $this->cached('tmdb_movie_' . $movieId, now()->addHours(6), 'movie_detail', function () use ($movieId) {
            $url = 'https://api.themoviedb.org/3/movie/' . $movieId . '?'
                . http_build_query(['append_to_response' => 'videos,credits,similar,recommendations,keywords,watch/providers']);
            return json_decode($this->fetch($url, 'movie_detail'));
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

        return json_decode($this->fetch($url, 'movie_similar'), true);
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

        return $this->cached("tmdb_trending_{$period}", $ttl, 'trending_movie', function () use ($period) {
            return json_decode($this->fetch("https://api.themoviedb.org/3/trending/movie/{$period}", 'trending_movie'), true);
        });
    }

    public function trendingTv(string $period = 'day'): array
    {
        $period = $period === 'week' ? 'week' : 'day';
        $ttl = $period === 'day'
            ? now('UTC')->diffInSeconds(\Carbon\Carbon::tomorrow('UTC'))
            : now('UTC')->diffInSeconds(\Carbon\Carbon::now('UTC')->startOfWeek(\Carbon\Carbon::MONDAY)->addWeek());

        return $this->cached("tmdb_trending_tv_{$period}", $ttl, 'trending_tv', function () use ($period) {
            return json_decode($this->fetch("https://api.themoviedb.org/3/trending/tv/{$period}", 'trending_tv'), true);
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
        return json_decode($this->fetch($url, 'search_movie'), true);
    }

    public function searchPeople(string $query): array
    {
        $url = 'https://api.themoviedb.org/3/search/person?' . http_build_query([
            'language'      => 'en-US',
            'query'         => $query,
            'page'          => 1,
            'include_adult' => 'false',
        ]);
        return json_decode($this->fetch($url, 'search_people'), true);
    }

    public function person(int $id): object
    {
        return $this->cached('tmdb_person_' . $id, now()->addHour(), 'person', function () use ($id) {
            $url = 'https://api.themoviedb.org/3/person/' . $id . '?' . http_build_query(['language' => 'en-US']);
            return json_decode($this->fetch($url, 'person'));
        });
    }

    /**
     * Full person detail with combined_credits. Cached 6 hours.
     */
    public function personDetail(int $id): object
    {
        return $this->cached('tmdb_person_detail_' . $id, now()->addHours(6), 'person_detail', function () use ($id) {
            $url = 'https://api.themoviedb.org/3/person/' . $id . '?'
                . http_build_query(['language' => 'en-US', 'append_to_response' => 'combined_credits,images']);
            return json_decode($this->fetch($url, 'person_detail'));
        });
    }

    public function collection(int $id): object
    {
        return $this->cached('tmdb_collection_' . $id, now()->addWeek(), 'collection', function () use ($id) {
            $url = 'https://api.themoviedb.org/3/collection/' . $id . '?' . http_build_query(['language' => 'en-US']);
            return json_decode($this->fetch($url, 'collection'));
        });
    }

    /**
     * Genre list — callers should use MovieService::genres() which caches the result.
     */
    public function genres(): string
    {
        $url = 'https://api.themoviedb.org/3/genre/movie/list?' . http_build_query(['language' => 'en-US']);
        return $this->fetch($url, 'genre_movie');
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

        unset($input['with_cast_names'], $input['with_crew_names']);

        foreach (['with_cast', 'with_crew'] as $key) {
            if (!empty($input[$key])) {
                $input['with_people'] = array_merge(
                    $input['with_people'] ?? [],
                    is_array($input[$key]) ? $input[$key] : [$input[$key]]
                );
            }
            unset($input[$key]);
        }

        foreach (['with_genres', 'without_genres', 'with_watch_providers', 'with_people', 'with_keywords'] as $key) {
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

        // Always exclude talk shows, news, and reality TV (genres 10767, 10763, 10764)
        $nonScripted = ['10767', '10763', '10764'];
        if (isset($input['without_genres'])) {
            $input['without_genres'] = implode(',', array_unique(array_merge(
                explode(',', $input['without_genres']), $nonScripted
            )));
        } else {
            $input['without_genres'] = implode(',', $nonScripted);
        }

        if (isset($input['with_watch_providers'])) {
            $input['watch_region'] = $country;
        }

        $url = 'https://api.themoviedb.org/3/discover/tv?' . http_build_query($input);
        return json_decode($this->fetch($url, 'discover_tv'), true);
    }

    /**
     * Lightweight show status — only status + last_air_date. Cached 24 h.
     */
    public function tvStatus(int $id): array
    {
        return $this->cached('tmdb_tv_status_' . $id, now()->addHours(24), 'tv_status', function () use ($id) {
            $url  = 'https://api.themoviedb.org/3/tv/' . $id . '?' . http_build_query(['language' => 'en-US']);
            $data = json_decode($this->fetch($url, 'tv_status'), true);
            return [
                'status'        => $data['status'] ?? null,
                'last_air_date' => $data['last_air_date'] ?? null,
            ];
        });
    }

    /**
     * Episode detail — guest stars, crew, images. Cached 6 hours.
     */
    public function tvEpisode(int $showId, int $seasonNumber, int $episodeNumber): object
    {
        return $this->cached("tmdb_tv_{$showId}_s{$seasonNumber}_e{$episodeNumber}", now()->addHours(6), 'tv_episode', function () use ($showId, $seasonNumber, $episodeNumber) {
            $url = "https://api.themoviedb.org/3/tv/{$showId}/season/{$seasonNumber}/episode/{$episodeNumber}?"
                . http_build_query(['language' => 'en-US', 'append_to_response' => 'credits,images']);
            return json_decode($this->fetch($url, 'tv_episode'));
        });
    }

    /**
     * Season detail — all episodes, cast, and crew. Cached 6 hours.
     */
    public function tvSeason(int $showId, int $seasonNumber): object
    {
        return $this->cached("tmdb_tv_{$showId}_season_{$seasonNumber}", now()->addHours(6), 'tv_season', function () use ($showId, $seasonNumber) {
            $url = "https://api.themoviedb.org/3/tv/{$showId}/season/{$seasonNumber}?"
                . http_build_query(['language' => 'en-US', 'append_to_response' => 'credits']);
            return json_decode($this->fetch($url, 'tv_season'));
        });
    }

    /**
     * Full TV show details with videos, credits, similar, and watch/providers appended.
     * Cached per show ID for 6 hours.
     */
    public function tvShow(int $id): object
    {
        return $this->cached('tmdb_tv_' . $id, now()->addHours(6), 'tv_detail', function () use ($id) {
            $url = 'https://api.themoviedb.org/3/tv/' . $id . '?'
                . http_build_query(['append_to_response' => 'videos,credits,similar,recommendations,keywords,watch/providers,external_ids']);
            return json_decode($this->fetch($url, 'tv_detail'));
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

        return json_decode($this->fetch($url, 'tv_similar'), true);
    }

    /** TV genre list — use MovieService::genres($tmdb, 'tv') which caches the result. */
    public function tvGenres(): string
    {
        $url = 'https://api.themoviedb.org/3/genre/tv/list?' . http_build_query(['language' => 'en-US']);
        return $this->fetch($url, 'genre_tv');
    }

    public function searchTv(string $query): array
    {
        $url = 'https://api.themoviedb.org/3/search/tv?' . http_build_query([
            'language'      => 'en-US',
            'query'         => $query,
            'page'          => 1,
            'include_adult' => 'false',
        ]);
        return json_decode($this->fetch($url, 'search_tv'), true);
    }

    public function searchAll(string $query): array
    {
        $url = 'https://api.themoviedb.org/3/search/multi?' . http_build_query([
            'query'         => $query,
            'language'      => 'en-US',
            'include_adult' => 'false',
        ]);
        return json_decode($this->fetch($url, 'search_all'), true);
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

    // ── Logging ──────────────────────────────────────────────────────────────

    /**
     * Make an HTTP GET request, log it, and return the response body string.
     * Uses finally so the log is written even if the request throws.
     */
    private function fetch(string $url, string $endpoint): string
    {
        $start      = microtime(true);
        $statusCode = 200;
        try {
            $response   = $this->client->get($url);
            $statusCode = $response->getStatusCode();
            return $response->getBody()->getContents();
        } catch (RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;
            throw $e;
        } finally {
            $ms = (int) round((microtime(true) - $start) * 1000);
            $this->log($endpoint, false, $statusCode, $ms);
        }
    }

    /**
     * Cache::remember wrapper that logs cache hits.
     * Cache misses are logged inside the closure via fetch().
     */
    private function cached(string $key, mixed $ttl, string $endpoint, callable $fn): mixed
    {
        $called = false;
        $result = Cache::remember($key, $ttl, function () use ($fn, &$called) {
            $called = true;
            return $fn();
        });
        if (!$called) {
            $this->log($endpoint, true, null, null);
        }
        return $result;
    }

    private function log(string $endpoint, bool $cached, ?int $statusCode, ?int $ms): void
    {
        try {
            TmdbRequestLog::create([
                'endpoint'         => $endpoint,
                'cached'           => $cached,
                'status_code'      => $statusCode,
                'response_time_ms' => $ms,
                'user_id'          => auth()->id(),
                'visitor_hash'     => $this->visitorHash(),
            ]);
        } catch (\Throwable) {
            // Never let logging break a request
        }
    }

    private function visitorHash(): string
    {
        try {
            $ip = request()->ip() ?? '';
            $ua = request()->userAgent() ?? '';
            return substr(hash('sha256', $ip . $ua), 0, 16);
        } catch (\Throwable) {
            return '';
        }
    }
}
