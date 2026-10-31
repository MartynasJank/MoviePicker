<?php

namespace App\Http\Middleware;

use App\Models\CollabBatch;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CollabParticipant
{
    public function handle(Request $request, Closure $next): Response
    {
        $batch = CollabBatch::where('token', $request->route('token'))
            ->where('expires_at', '>', now())->firstOrFail();
        $isJoining = $request->is('batch/collab/*/join');
        $userId = $request->session()->get('collab_participant_id');

        if ($isJoining && !$userId) {
            $userId = (string) Str::uuid();
            $request->session()->put('collab_participant_id', $userId);
        }

        abort_unless($userId, 403);
        if (!$isJoining) {
            abort_unless(collect($batch->participants ?? [])->contains('userId', $userId), 403);
        }

        $request->validate(['name' => 'sometimes|required|string|max:80']);
        $name = $request->input('name')
            ?? collect($batch->participants ?? [])->firstWhere('userId', $userId)['name'] ?? 'Anonymous';
        $request->merge(['userId' => $userId, 'name' => $name]);

        return $next($request);
    }
}
