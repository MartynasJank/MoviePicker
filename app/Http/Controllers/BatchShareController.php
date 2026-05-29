<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;

class BatchShareController extends Controller
{
    public function show(string $token)
    {
        $cacheKey = 'batch_share_' . substr(hash('sha256', $token), 0, 24);

        $data = Cache::remember($cacheKey, now()->addDays(30), function () use ($token) {
            return self::decode($token);
        });

        if (!$data) {
            abort(404);
        }

        $isTv    = $data['type'] === 'tv';
        $results = $data['movies'];

        return view('batch', [
            'movies'      => ['results' => $results],
            'all_genres'  => [],
            'movie_genres'=> [],
            'providersArray' => [],
            'user_input'  => [],
            'tag'         => $isTv ? 'Shared TV Batch' : 'Shared Movie Batch',
            'savedIds'    => auth()->check()
                ? auth()->user()->watchlist()->pluck('tmdb_id')->toArray()
                : [],
            'mediaType'   => $isTv ? 'tv' : 'movie',
            'isShared'    => true,
        ]);
    }

    public static function encode(array $results, string $type): string
    {
        $data = [
            'type'   => $type,
            'movies' => array_map(fn($m) => [
                'id'           => $m['id'],
                'poster_path'  => $m['poster_path'] ?? null,
                'title'        => $m['title'] ?? $m['name'] ?? '',
                'name'         => $m['name'] ?? $m['title'] ?? '',
                'vote_average' => $m['vote_average'] ?? 0,
                'release_date' => $m['release_date'] ?? $m['first_air_date'] ?? null,
                'first_air_date' => $m['first_air_date'] ?? null,
                'genre_ids'    => $m['genre_ids'] ?? [],
            ], array_values($results)),
        ];

        return rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');
    }

    private static function decode(string $token): ?array
    {
        try {
            $json = base64_decode(strtr($token, '-_', '+/') . str_repeat('=', strlen($token) % 4));
            $data = json_decode($json, true);
            if (!isset($data['type'], $data['movies']) || !is_array($data['movies'])) {
                return null;
            }
            return $data;
        } catch (\Throwable) {
            return null;
        }
    }
}
