<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function savedWatchlistIds(): array
    {
        return auth()->check()
            ? auth()->user()->watchlist()->pluck('tmdb_id')->toArray()
            : [];
    }

    protected function toRollCards(array $items, string $mediaType = 'movie'): array
    {
        $isTv = $mediaType === 'tv';
        return array_map(fn($m) => [
            'title'        => $isTv ? ($m['name'] ?? $m['title'] ?? '') : ($m['title'] ?? ''),
            'poster_path'  => $m['poster_path'] ?? null,
            'vote_average' => $m['vote_average'] ?? 0,
            'url'          => $isTv ? route('tv.show', $m['id']) : route('movie', $m['id']),
        ], $items);
    }
}
