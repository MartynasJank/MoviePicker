<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MovieService;
use App\Services\TmdbClient;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SwipeV2Controller extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.swipe-v2', [
            'watchlistIds' => $request->user()->watchlist()->where('type', 'movie')->pluck('tmdb_id')->all(),
        ]);
    }

    public function feed(Request $request, MovieService $movieService, TmdbClient $tmdb): JsonResponse
    {
        $input = $request->validate(['exclude_page' => ['sometimes', 'integer', 'min:1', 'max:500']]);

        try {
            $results = $movieService->discoverRandomMovies($tmdb, [
                'with_original_language' => 'en',
                'primary_release_date_gte' => 1990,
                'vote_average_gte' => 6,
                'vote_count_gte' => 50,
            ], $movieService->getUserCountry(), (int) ($input['exclude_page'] ?? 0));
        } catch (GuzzleException $exception) {
            report($exception);

            return response()->json(['message' => 'Could not load movies. Please try again.'], 502);
        }

        return response()->json([
            'movies' => array_map(fn (array $movie): array => [
                'id' => $movie['id'],
                'title' => $movie['title'],
                'url' => route('movie', ['id' => $movie['id']]),
            ], $results['results']),
            'page' => $results['page'],
            'has_more' => $results['has_more'],
        ]);
    }

    public function show(int $id, MovieService $movieService, TmdbClient $tmdb): JsonResponse
    {
        try {
            $movie = $tmdb->movie($id);
        } catch (GuzzleException $exception) {
            report($exception);

            return response()->json(['message' => 'Could not load this trailer. Please try again.'], 502);
        }

        return response()->json([
            'trailer' => $movieService->getSwipeTrailer($movie->videos->results ?? []),
            'overview' => $movie->overview ?? '',
            'year' => substr($movie->release_date ?? '', 0, 4) ?: null,
            'runtime' => $movie->runtime ?? null,
            'genres' => $movieService->genresString($movie),
            'vote_average' => ($movie->vote_count ?? 0) > 0 ? ($movie->vote_average ?? null) : null,
            'poster_path' => $movie->poster_path ?? null,
        ]);
    }
}
