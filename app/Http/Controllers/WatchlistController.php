<?php

namespace App\Http\Controllers;

use App\Models\Watchlist;
use App\Services\MovieService;
use App\Services\TmdbClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WatchlistController extends Controller
{
    /**
     * Normalize TV compound genre names to canonical movie-style names so the
     * watchlist genre filter works uniformly for both media types.
     *   "Action & Adventure" → "Action, Adventure"
     *   "Sci-Fi & Fantasy"   → "Sci-Fi, Fantasy"
     *   "War & Politics"     → "War"
     */
    private static function normalizeGenres(string $raw): string
    {
        $expansions = [
            'Action & Adventure' => ['Action', 'Adventure'],
            'Sci-Fi & Fantasy'   => ['Sci-Fi', 'Fantasy'],
            'War & Politics'     => ['War'],
        ];

        $normalized = [];
        foreach (array_map('trim', explode(',', $raw)) as $genre) {
            if ($genre === '') continue;
            if (isset($expansions[$genre])) {
                foreach ($expansions[$genre] as $g) {
                    $normalized[] = $g;
                }
            } else {
                $normalized[] = $genre;
            }
        }

        return implode(', ', array_unique($normalized));
    }

    public function index(TmdbClient $tmdb, MovieService $movieService)
    {
        session(['batchUrl' => route('watchlist')]);

        $items = Auth::user()
            ->watchlist()
            ->orderByDesc('created_at')
            ->get();

        // Back-fill genres for items saved without them (up to 15 per load)
        $missing = $items->whereNull('genres');
        if ($missing->isNotEmpty()) {
            $movieGenreList = null;
            $tvGenreList    = null;
            foreach ($missing->take(15) as $item) {
                try {
                    if ($item->type === 'tv') {
                        $tvGenreList ??= $movieService->genres($tmdb, 'tv');
                        $info = $tmdb->tvShow($item->tmdb_id);
                    } else {
                        $movieGenreList ??= $movieService->genres($tmdb, 'movie');
                        $info = $tmdb->movie($item->tmdb_id);
                    }
                    $genreString = $movieService->genresString($info);
                    if ($genreString) {
                        $genreString = static::normalizeGenres($genreString);
                        $item->update(['genres' => $genreString]);
                        $item->genres = $genreString;
                    }
                } catch (\Throwable) {}
            }
        }

        // Normalize existing items that still carry TV compound genre names
        $compound = ['Action & Adventure', 'Sci-Fi & Fantasy', 'War & Politics'];
        $toNormalize = $items->filter(fn($i) =>
            $i->genres && collect($compound)->contains(fn($c) => str_contains($i->genres, $c))
        );
        foreach ($toNormalize as $item) {
            $normalized = static::normalizeGenres($item->genres);
            $item->update(['genres' => $normalized]);
            $item->genres = $normalized;
        }

        $genres = $items->pluck('genres')
            ->filter()
            ->flatMap(fn($g) => array_map('trim', explode(',', $g)))
            ->unique()
            ->sort()
            ->values();

        return view('watchlist', ['items' => $items, 'genres' => $genres]);
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'tmdb_id'      => 'required|integer',
            'title'        => 'required|string|max:255',
            'poster_path'  => 'nullable|string',
            'year'         => 'nullable|integer',
            'genres'       => 'nullable|string',
            'vote_average' => 'nullable|numeric|min:0|max:10',
            'type'         => 'nullable|in:movie,tv',
        ]);

        $user = Auth::user();
        $existing = $user->watchlist()->where('tmdb_id', $request->tmdb_id)->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['saved' => false]);
        }

        $rawGenres = $request->genres;

        $user->watchlist()->create([
            'tmdb_id'      => $request->tmdb_id,
            'title'        => $request->title,
            'poster_path'  => $request->poster_path,
            'year'         => $request->year,
            'genres'       => $rawGenres ? static::normalizeGenres($rawGenres) : null,
            'vote_average' => $request->vote_average ?: null,
            'type'         => $request->input('type', 'movie'),
            'status'       => 'saved',
        ]);

        return response()->json(['saved' => true]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'tmdb_id'      => 'required|integer',
            'title'        => 'required|string|max:255',
            'poster_path'  => 'nullable|string',
            'year'         => 'nullable|integer',
            'genres'       => 'nullable|string',
            'vote_average' => 'nullable|numeric|min:0|max:10',
            'type'         => 'nullable|in:movie,tv',
        ]);

        $user = Auth::user();
        if ($user->watchlist()->where('tmdb_id', $request->tmdb_id)->exists()) {
            return response()->json(['saved' => true]);
        }

        $user->watchlist()->create([
            'tmdb_id'      => $request->tmdb_id,
            'title'        => $request->title,
            'poster_path'  => $request->poster_path,
            'year'         => $request->year,
            'genres'       => $request->genres ? static::normalizeGenres($request->genres) : null,
            'vote_average' => $request->vote_average ?: null,
            'type'         => $request->input('type', 'movie'),
            'status'       => 'saved',
        ]);

        return response()->json(['saved' => true]);
    }

    public function remove(Request $request, $tmdbId)
    {
        Auth::user()->watchlist()->where('tmdb_id', $tmdbId)->delete();
        return response()->json(['ok' => true]);
    }

    public function setStatus(Request $request, $tmdbId)
    {
        $request->validate(['status' => 'required|in:saved,watched']);

        Auth::user()
            ->watchlist()
            ->where('tmdb_id', $tmdbId)
            ->update(['status' => $request->status]);

        return response()->json(['ok' => true]);
    }

    public function roll(Request $request)
    {
        $status = $request->query('status', 'all');
        $type   = $request->query('type', 'all');
        $genres = $request->query('genres', '');

        $query = Auth::user()->watchlist();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $items = $query->get();

        if ($type !== 'all') {
            $items = $items->filter(fn($item) => ($item->type ?? 'movie') === $type);
        }

        if ($genres) {
            $genreList = array_map('trim', explode(',', $genres));
            $items = $items->filter(function ($item) use ($genreList) {
                if (!$item->genres) return false;
                $cardGenres = array_map('trim', explode(',', $item->genres));
                return !empty(array_intersect($genreList, $cardGenres));
            });
        }

        if ($items->isEmpty()) {
            return redirect()->route('watchlist');
        }

        $exclude   = (int) $request->query('exclude', 0);
        $pickFrom  = $exclude ? $items->filter(fn($i) => $i->tmdb_id !== $exclude) : $items;
        if ($pickFrom->isEmpty()) $pickFrom = $items;

        session(['roll_source' => 'other']);
        $picked = $pickFrom->random();

        $base = $picked->type === 'tv'
            ? url('tv/' . $picked->tmdb_id)
            : route('movie', $picked->tmdb_id);

        $url = $base . '?wl_status=' . urlencode($status);
        if ($genres) $url .= '&wl_genres=' . urlencode($genres);
        if ($type !== 'all') $url .= '&wl_type=' . urlencode($type);

        return redirect($url);
    }
}
