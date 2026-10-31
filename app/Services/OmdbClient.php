<?php

namespace App\Services;

use App\Interfaces\MovieApiInterface as ApiMovie;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OmdbClient implements ApiMovie
{
    public function movie(mixed $imdbId): ?object
    {
        return $this->details($imdbId, 'movie');
    }

    public function tv(mixed $imdbId): ?object
    {
        return $this->details($imdbId, 'tv');
    }

    /**
     * OMDb enriches TMDB details; unavailable ratings must not prevent a page from loading.
     */
    private function details(mixed $imdbId, string $type): ?object
    {
        $apiKey = trim((string) config('api.OMDB'));

        if (!config('api.omdb_enabled') || $apiKey === '' || $apiKey === 'No Key' || empty($imdbId)) {
            return null;
        }

        return Cache::remember('omdb_' . $type . '_' . $imdbId, now()->addHours(6), function () use ($imdbId, $apiKey): ?object {
            try {
                $response = Http::connectTimeout(2)->timeout(3)
                    ->get('https://private.omdbapi.com/', [
                        'apikey' => $apiKey,
                        'i' => $imdbId,
                        'tomatoes' => 'true',
                    ]);
            } catch (ConnectionException) {
                return null;
            }

            if (!$response->successful()) {
                return null;
            }

            $data = json_decode($response->body());

            return is_object($data) && ($data->Response ?? null) === 'True' ? $data : null;
        });
    }
}
