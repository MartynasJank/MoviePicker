<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(TmdbClient $tmdb): View
    {
        $savedIds = $this->savedWatchlistIds();

        $normalize = fn(array $data) => array_merge($data, [
            'results' => array_map(fn($s) => array_merge($s, [
                'title'        => $s['name'] ?? $s['title'] ?? '',
                'release_date' => $s['first_air_date'] ?? $s['release_date'] ?? '',
            ]), $data['results'] ?? []),
        ]);

        return view('home', [
            'trendingDay'   => $tmdb->trending('day'),
            'tvTrendingDay' => $normalize($tmdb->trendingTv('day')),
            'savedIds'      => $savedIds,
        ]);
    }
}
