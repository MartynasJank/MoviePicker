<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class MovieService
{
    public function randomPage(int $totalPages, int $exclude = 0): int
    {
        $capped = min($totalPages, 500);
        if ($capped <= 1) return 1;

        do {
            $page = rand(1, $capped);
        } while ($page === $exclude);

        return $page;
    }

    public function randomMovie(array $movieArray): array
    {
        return $movieArray[array_rand($movieArray)];
    }

    /** Shuffle results and return at most $size items. */
    public function pickBatch(array $results, int $size = 20): array
    {
        if (count($results) <= $size) {
            return $results;
        }
        shuffle($results);
        return array_slice($results, 0, $size);
    }

    /**
     * Resolve the criteria array to use for discovery.
     * On first visit stores submitted criteria in session; subsequent visits
     * always return the session copy so "pick another" keeps the same filters.
     */
    public function resolveSessionCriteria(array $submitted, bool $overwrite = false): array
    {
        if ($overwrite || session('userInput') === null) {
            session()->put('userInput', $submitted);
        }

        return session('userInput');
    }

    /**
     * Resolve the page number to discover.
     * Uses the cached total_pages if available, otherwise fetches it and caches it.
     */
    public function resolvePage(TmdbClient $tmdb, array $criteria, string $country): int
    {
        if (empty($criteria)) {
            return $this->randomPage(500);
        }

        if (isset($criteria['total_pages'])) {
            return $this->randomPage($criteria['total_pages']);
        }

        $all = $tmdb->discover($criteria, $country);
        session()->put('userInput.total_pages', $all['total_pages']);
        return $this->randomPage($all['total_pages']);
    }

    /**
     * Resolve criteria with a random page for a named roulette type.
     * Caches total_pages in session so repeated rolls skip the extra API call.
     */
    public function resolveRoulettePage(TmdbClient $tmdb, array $criteria, string $type, string $country): array
    {
        if (session('roulette.type') !== $type) {
            session()->forget('roulette');
        }

        if (session()->has('roulette.total_pages')) {
            $criteria['page'] = $this->randomPage(session('roulette.total_pages'));
        } else {
            $all = $tmdb->discover($criteria, $country);
            session()->put('roulette', ['type' => $type, 'total_pages' => $all['total_pages']]);
            $criteria['page'] = $this->randomPage($all['total_pages']);
        }

        return $criteria;
    }

    /** Like resolveRoulettePage() but uses discoverTv() for TV roulettes. */
    public function resolveRoulettePageTv(TmdbClient $tmdb, array $criteria, string $type, string $country): array
    {
        $sessionKey = 'roulette_tv';

        if (session($sessionKey . '.type') !== $type) {
            session()->forget($sessionKey);
        }

        if (session()->has($sessionKey . '.total_pages')) {
            $criteria['page'] = $this->randomPage(session($sessionKey . '.total_pages'));
        } else {
            $all = $tmdb->discoverTv($criteria, $country);
            session()->put($sessionKey, ['type' => $type, 'total_pages' => $all['total_pages']]);
            $criteria['page'] = $this->randomPage($all['total_pages']);
        }

        return $criteria;
    }

    /** Genre list, cached for one week. */
    public function genres(TmdbClient $tmdb): array
    {
        return Cache::remember('tmdb_genres', now()->addWeek(), function () use ($tmdb) {
            return json_decode($tmdb->genres())->genres;
        });
    }

    /** TV genre list, cached for one week. */
    public function tvGenres(TmdbClient $tmdb): array
    {
        return Cache::remember('tmdb_tv_genres', now()->addWeek(), function () use ($tmdb) {
            return json_decode($tmdb->tvGenres())->genres;
        });
    }

    /**
     * Resolve the page number for TV show discovery.
     * Mirrors resolvePage() but uses discoverTv() and the tvInput session key.
     */
    public function resolveTvPage(TmdbClient $tmdb, array $criteria, string $country): int
    {
        if (empty($criteria)) {
            return $this->randomPage(500);
        }

        if (isset($criteria['total_pages'])) {
            return $this->randomPage($criteria['total_pages']);
        }

        $all = $tmdb->discoverTv($criteria, $country);
        session()->put('tvInput.total_pages', $all['total_pages']);
        return $this->randomPage($all['total_pages']);
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
        $ip = \Request::header('CF-Connecting-IP') ?: \Request::ip();
        try {
            $data = \Location::get($ip);
        } catch (\Throwable) {
            $data = null;
        }

        return $data->countryCode ?? 'LT';
    }

    public function tvCreditFilter(int $minEpisodes = 5): \Closure
    {
        $nonScripted = [10767, 10763, 10764];

        return fn($c) => ($c->media_type ?? '') === 'tv'
            && !empty($c->id)
            && ($c->vote_count ?? 0) >= 10
            && ($c->episode_count ?? 0) >= $minEpisodes
            && empty(array_intersect((array)($c->genre_ids ?? []), $nonScripted));
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
