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

        $type    = $data['type'];
        $isTv    = $type === 'tv';
        $isMixed = $type === 'mixed';
        $results = $data['movies'];

        $tags = match(true) {
            $isMixed => 'Shared Watchlist',
            $isTv    => 'Shared TV Batch',
            default  => 'Shared Movie Batch',
        };

        $titles   = collect($results)->pluck('title')->filter()->take(4)->implode(', ');
        $ogImage  = collect($results)->first(fn($m) => !empty($m['poster_path']));
        $ogImage  = $ogImage ? 'https://image.tmdb.org/t/p/w500' . $ogImage['poster_path'] : null;

        return view('batch', [
            'movies'         => ['results' => $results],
            'all_genres'     => [],
            'movie_genres'   => [],
            'providersArray' => [],
            'user_input'     => [],
            'tag'            => $tags,
            'savedIds'       => auth()->check()
                ? auth()->user()->watchlist()->pluck('tmdb_id')->toArray()
                : [],
            'mediaType'      => $isMixed ? 'mixed' : ($isTv ? 'tv' : 'movie'),
            'isShared'       => true,
            'shareToken'     => self::encode($results, $type),
            'ogTitle'        => $tags . ' — MoviePickr',
            'ogDescription'  => $titles ? 'Includes: ' . $titles . '...' : $tags,
            'ogImage'        => $ogImage,
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
            $padded = strtr($token, '-_', '+/');
            $padded .= str_repeat('=', (4 - strlen($padded) % 4) % 4);
            $json   = base64_decode($padded, strict: true) ?: base64_decode($padded);
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
