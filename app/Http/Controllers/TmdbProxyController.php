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
}
