<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TmdbProxyController extends Controller
{
    public function searchMovies(Request $request, TmdbClient $tmdb): JsonResponse
    {
        $query = trim($request->string('q'));
        if (!$query) {
            return response()->json([]);
        }

        $results = collect($tmdb->searchMovies($query)['results'] ?? [])
            ->take(6)
            ->values()
            ->all();

        return response()->json($results);
    }

    public function searchPeople(Request $request, TmdbClient $tmdb): JsonResponse
    {
        $query = trim($request->string('q'));
        $dept  = $request->string('dept')->value();
        if (!$query) {
            return response()->json([]);
        }

        $exclude = $request->string('exclude_dept')->value();

        $results = collect($tmdb->searchPeople($query)['results'] ?? [])
            ->when($dept,    fn($c) => $c->where('known_for_department', $dept))
            ->when($exclude, fn($c) => $c->where('known_for_department', '!=', $exclude))
            ->sortByDesc('popularity')
            ->take(4)
            ->values()
            ->all();

        return response()->json($results);
    }

    public function person(int $id, TmdbClient $tmdb): JsonResponse
    {
        return response()->json($tmdb->person($id));
    }

    public function searchTv(Request $request, TmdbClient $tmdb): JsonResponse
    {
        $query = trim($request->string('q'));
        if (!$query) {
            return response()->json([]);
        }

        $results = collect($tmdb->searchTv($query)['results'] ?? [])
            ->map(function ($show) {
                $show['title']        = $show['name'] ?? '';
                $show['release_date'] = $show['first_air_date'] ?? '';
                return $show;
            })
            ->take(4)
            ->values()
            ->map(function ($show) use ($tmdb) {
                try {
                    $status = $tmdb->tvStatus($show['id']);
                    $show['tv_status']     = $status['status'];
                    $show['last_air_date'] = $status['last_air_date'];
                } catch (\Throwable) {}
                return $show;
            })
            ->all();

        return response()->json($results);
    }

    public function searchAll(Request $request, TmdbClient $tmdb): JsonResponse
    {
        $query = trim($request->string('q'));
        if (!$query) {
            return response()->json(['movies' => [], 'shows' => [], 'people' => []]);
        }

        $results = collect($tmdb->searchAll($query)['results'] ?? []);

        $movies = $results
            ->where('media_type', 'movie')
            ->take(3)
            ->values()
            ->all();

        $shows = $results
            ->where('media_type', 'tv')
            ->take(3)
            ->map(fn($s) => array_merge($s, [
                'title'        => $s['name'] ?? '',
                'release_date' => $s['first_air_date'] ?? '',
            ]))
            ->values()
            ->all();

        $people = $results
            ->where('media_type', 'person')
            ->take(3)
            ->values()
            ->all();

        return response()->json(compact('movies', 'shows', 'people'));
    }
}
