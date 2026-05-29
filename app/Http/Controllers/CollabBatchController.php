<?php

namespace App\Http\Controllers;

use App\Events\BatchComplete;
use App\Events\MovieVetoed;
use App\Models\CollabBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CollabBatchController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'movies'     => 'required|array|min:2',
            'media_type' => 'in:movie,tv,mixed',
        ]);

        $batch = CollabBatch::create([
            'token'      => Str::random(8),
            'movies'     => $request->movies,
            'media_type' => $request->input('media_type', 'movie'),
            'created_by' => auth()->id(),
            'expires_at' => now()->addHours(24),
        ]);

        return response()->json(['token' => $batch->token]);
    }

    public function show(string $token)
    {
        $batch = CollabBatch::where('token', $token)
            ->where('expires_at', '>', now())
            ->firstOrFail();

        return view('batch.collab', compact('batch'));
    }

    public function veto(string $token, int $movieId, Request $request)
    {
        $batch = CollabBatch::where('token', $token)
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $movies = collect($batch->movies)->reject(fn($m) => $m['id'] === $movieId)->values()->all();
        $batch->update(['movies' => $movies]);

        $vetoedBy = auth()->user()?->name ?? $request->input('identity', 'Someone');

        if (count($movies) === 1) {
            broadcast(new BatchComplete($token, $movies[0], $vetoedBy))->toOthers();
            return response()->json(['remaining' => 1, 'winner' => $movies[0]]);
        }

        broadcast(new MovieVetoed($token, $movieId, $vetoedBy, $movies))->toOthers();

        return response()->json(['remaining' => count($movies)]);
    }
}
