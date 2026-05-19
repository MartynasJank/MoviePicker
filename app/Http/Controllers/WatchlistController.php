<?php

namespace App\Http\Controllers;

use App\Models\Watchlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WatchlistController extends Controller
{
    public function index()
    {
        $items = Auth::user()
            ->watchlist()
            ->orderByDesc('created_at')
            ->get();

        return view('watchlist', ['items' => $items]);
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'tmdb_id'     => 'required|integer',
            'title'       => 'required|string|max:255',
            'poster_path' => 'nullable|string',
            'year'        => 'nullable|integer',
            'genres'      => 'nullable|string',
        ]);

        $user = Auth::user();
        $existing = $user->watchlist()->where('tmdb_id', $request->tmdb_id)->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['saved' => false]);
        }

        $user->watchlist()->create([
            'tmdb_id'     => $request->tmdb_id,
            'title'       => $request->title,
            'poster_path' => $request->poster_path,
            'year'        => $request->year,
            'genres'      => $request->genres,
            'status'      => 'saved',
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
}
