<?php

namespace App\Http\Controllers;

use App\Events\CollabStateUpdated;
use App\Models\CollabBatch;
use App\Services\MovieService;
use App\Services\TmdbClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CollabBatchController extends Controller
{
    // ── Thresholds ────────────────────────────────────────────────────

    private function activeParticipants(CollabBatch $batch): array
    {
        return collect($batch->participants ?? [])
            ->filter(fn($p) => Carbon::parse($p['last_seen'])->gt(now()->subMinutes(3)))
            ->values()
            ->all();
    }

    private function participantCount(CollabBatch $batch): int
    {
        return max(1, count($this->activeParticipants($batch)));
    }

    private function vetoThreshold(int $n): int    { return (int) floor($n / 2) + 1; }
    private function readyThreshold(int $n): int   { return (int) ceil($n * 0.75); }
    private function refreshThreshold(int $n): int { return $n; }

    // ── Helpers ───────────────────────────────────────────────────────

    private function slim(array $movie): array
    {
        return [
            'id'             => $movie['id'],
            'poster_path'    => $movie['poster_path'] ?? null,
            'title'          => $movie['title'] ?? $movie['name'] ?? '',
            'vote_average'   => $movie['vote_average'] ?? 0,
            'media_type'     => $movie['media_type'] ?? null,
            'release_date'   => $movie['release_date'] ?? null,
            'first_air_date' => $movie['first_air_date'] ?? null,
            'genres'         => $movie['genres'] ?? '',
        ];
    }

    private function slimAll(array $movies): array
    {
        return array_values(array_map(fn($m) => $this->slim($m), $movies));
    }

    private function broadcast(string $token, string $type, string $byName = '', string $byId = '', string $movieTitle = '', array $delta = []): void
    {
        broadcast(new CollabStateUpdated($token, $type, $byName, $byId, $movieTitle, $delta));
    }

    private function guardBatch(string $token): CollabBatch
    {
        $batch = CollabBatch::where('token', $token)
            ->where('expires_at', '>', now())
            ->firstOrFail();

        foreach ($batch->participants ?? [] as $participant) {
            if (isset($participant['departed_at']) && empty($participant['votes_cleared'])
                && Carbon::parse($participant['departed_at'])->lte(now()->subSeconds(CollabBatch::RECONNECT_GRACE_SECONDS))) {
                $this->clearParticipantVotes($batch, $participant['userId']);
            }
        }

        return $batch;
    }

    // ── Create ────────────────────────────────────────────────────────

    public function create(Request $request)
    {
        $request->validate([
            'movies'     => 'required|array|min:2|max:' . CollabBatch::MAX_MOVIES,
            'movies.*' => 'required|array',
            'movies.*.id' => 'required|integer|min:1',
            'movies.*.title' => 'sometimes|nullable|string|max:255',
            'movies.*.name' => 'sometimes|nullable|string|max:255',
            'movies.*.poster_path' => ['nullable', 'string', 'max:255', 'regex:~^/[a-zA-Z0-9._/-]+$~'],
            'movies.*.vote_average' => 'nullable|numeric|min:0|max:10',
            'movies.*.media_type' => 'nullable|in:movie,tv',
            'movies.*.release_date' => 'nullable|date_format:Y-m-d',
            'movies.*.first_air_date' => 'nullable|date_format:Y-m-d',
            'movies.*.genres' => 'nullable|string|max:1000',
            'media_type' => 'in:movie,tv,mixed',
        ]);

        $sessionKey = $request->input('media_type') === 'tv' ? 'tvInput' : 'userInput';
        $movies     = $this->slimAll($request->movies);

        $batch = CollabBatch::create([
            'token'      => Str::random(8),
            'movies'     => $movies,
            'media_type' => $request->input('media_type', 'movie'),
            'created_by' => auth()->id(),
            'expires_at' => now()->addHours(24),
            'criteria'   => session($sessionKey),
        ]);

        return response()->json(['token' => $batch->token]);
    }

    // ── Show ──────────────────────────────────────────────────────────

    public function show(string $token)
    {
        $batch = $this->guardBatch($token);
        if (!session()->has('collab_participant_id')) {
            session(['collab_participant_id' => (string) Str::uuid()]);
        }
        $participants = $this->activeParticipants($batch);
        return view('batch.collab', compact('batch', 'participants'));
    }

    // ── Participant tracking ──────────────────────────────────────────

    private function trackParticipant(Request $request, string $token, bool $joining): JsonResponse
    {
        $batch = $this->guardBatch($token);
        $userId = $request->input('userId');
        $name = $request->input('name', 'Anonymous');
        $previous = collect($batch->participants ?? [])->firstWhere('userId', $userId);
        $reconnecting = isset($previous['departed_at']);

        $connections = collect($previous['connections'] ?? [])
            ->filter(fn ($seen) => Carbon::parse($seen)->gt(now()->subMinutes(3)))->all();
        $connections[$request->input('connectionId', 'legacy')] = now()->toISOString();
        $participant = ['userId' => $userId, 'name' => $name, 'last_seen' => now()->toISOString(), 'connections' => $connections];
        $participants = collect($batch->participants ?? [])
            ->reject(fn ($item) => $item['userId'] === $userId)->push($participant)->values()->all();
        $batch->update(['participants' => $participants]);

        if ($joining || !$previous || $reconnecting) {
            $this->broadcast($token, 'join', $name, $userId, '', ['participant' => $participant]);
        } elseif ($previous['name'] !== $name) {
            $this->broadcast($token, 'rename', $name, $userId, $previous['name'], ['participant' => $participant]);
        }

        return response()->json(['ok' => true]);
    }

    public function join(Request $request, string $token): JsonResponse
    {
        return $this->trackParticipant($request, $token, true);
    }

    public function heartbeat(Request $request, string $token): JsonResponse
    {
        return $this->trackParticipant($request, $token, false);
    }

    public function leave(Request $request, string $token): JsonResponse
    {
        $batch = $this->guardBatch($token);
        $userId = $request->input('userId');
        $participant = collect($batch->participants ?? [])->firstWhere('userId', $userId);
        $connectionId = $request->input('connectionId', 'legacy');
        $connections = $participant['connections'] ?? ['legacy' => $participant['last_seen']];

        if (!array_key_exists($connectionId, $connections)) {
            return response()->json(['ok' => true]);
        }

        unset($connections[$connectionId]);
        $connections = collect($connections)
            ->filter(fn ($seen) => Carbon::parse($seen)->gt(now()->subMinutes(3)))->all();
        $participant['connections'] = $connections;
        if ($connections === []) {
            $participant['departed_at'] = now()->toISOString();
            $participant['last_seen'] = now()->subMinutes(3)->toISOString();
        }
        $changes = ['participants' => collect($batch->participants)
            ->map(fn ($item) => $item['userId'] === $userId ? $participant : $item)->all()];
        if ($connections === []) {
            $changes['ready'] = array_values(array_diff($batch->ready ?? [], [$userId]));
            $changes['refresh_votes'] = array_values(array_diff($batch->refresh_votes ?? [], [$userId]));
            $changes['try_again_votes'] = array_values(array_diff($batch->try_again_votes ?? [], [$userId]));
        }
        $batch->update($changes);

        if ($connections === []) {
            $this->broadcast($token, 'leave', $participant['name'], $userId, '', [
                'ready' => $batch->ready ?? [], 'refreshVotes' => $batch->refresh_votes ?? [],
                'tryAgainVotes' => $batch->try_again_votes ?? [],
            ]);
        }

        return response()->json(['ok' => true]);
    }

    private function clearParticipantVotes(CollabBatch $batch, string $userId): void
    {
        $participant = collect($batch->participants ?? [])->firstWhere('userId', $userId);
        if (!empty($participant['votes_cleared'])) {
            return;
        }

        $participants = collect($batch->participants ?? [])->map(function ($item) use ($userId) {
            if ($item['userId'] === $userId && isset($item['departed_at'])) {
                $item['votes_cleared'] = true;
            }
            return $item;
        })->all();
        $batch->update([
            'participants' => $participants,
            'votes' => collect($batch->votes ?? [])->map(fn ($voters) => array_values(array_diff($voters, [$userId])))->all(),
            'restore_votes' => collect($batch->restore_votes ?? [])->map(fn ($voters) => array_values(array_diff($voters, [$userId])))->all(),
            'ready' => array_values(array_diff($batch->ready ?? [], [$userId])),
            'refresh_votes' => array_values(array_diff($batch->refresh_votes ?? [], [$userId])),
            'try_again_votes' => array_values(array_diff($batch->try_again_votes ?? [], [$userId])),
        ]);
        $this->broadcast($batch->token, 'vote_cleanup', '', '', '', [
            'votes' => $batch->votes ?? [], 'restoreVotes' => $batch->restore_votes ?? [],
            'ready' => $batch->ready ?? [], 'refreshVotes' => $batch->refresh_votes ?? [],
            'tryAgainVotes' => $batch->try_again_votes ?? [],
        ]);
    }

    public function removeVotes(Request $request, string $token): JsonResponse
    {
        $batch = $this->guardBatch($token);
        $request->validate(['targetUserId' => 'sometimes|required|string|max:100']);
        $targetUserId = $request->input('targetUserId', $request->input('userId'));
        $participant = collect($batch->participants ?? [])->firstWhere('userId', $targetUserId);
        $departed = isset($participant['departed_at'])
            && Carbon::parse($participant['departed_at'])->lte(now()->subSeconds(CollabBatch::RECONNECT_GRACE_SECONDS));
        abort_unless($targetUserId === $request->input('userId') || $departed, 403);

        $this->clearParticipantVotes($batch, $targetUserId);

        return response()->json(['ok' => true]);
    }

    // ── Voting signal (ephemeral, no DB write) ────────────────────────

    public function voting(Request $request, string $token)
    {
        $batch   = $this->guardBatch($token);
        $userId  = $request->input('userId');
        $name    = $request->input('name', 'Someone');
        $movieId = (int) $request->input('movieId');

        if (!$movieId) return response()->json(['ok' => true]);

        $isRecalling = (bool) $request->input('isRecalling', false);
        $this->broadcast($token, 'voting', $name, $userId, '', ['movieId' => $movieId, 'isRecalling' => $isRecalling]);
        return response()->json(['ok' => true]);
    }

    // ── Voting ────────────────────────────────────────────────────────

    public function vote(Request $request, string $token, int $movieId)
    {
        $batch   = $this->guardBatch($token);
        $userId  = $request->input('userId');
        $name    = $request->input('name', 'Someone');
        $request->validate(['type' => 'sometimes|in:veto,restore']);
        $type    = $request->input('type', 'veto');
        $key     = (string) $movieId;
        $pCount  = $this->participantCount($batch);

        if ($type === 'veto') {
            $votes   = $batch->votes ?? [];
            $voters  = $votes[$key] ?? [];
            $removing = in_array($userId, $voters);

            $voters      = $removing
                ? array_values(array_diff($voters, [$userId]))
                : [...$voters, $userId];
            $votes[$key] = $voters;

            if (count($voters) >= $this->vetoThreshold($pCount)) {
                $movie = collect($batch->movies)->first(fn($m) => $m['id'] === $movieId);
                if ($movie) {
                    $graveyard = $batch->graveyard ?? [];
                    $graveyard[] = $movie;
                    $movies = collect($batch->movies)->reject(fn($m) => $m['id'] === $movieId)->values()->all();
                    unset($votes[$key]);
                    $batch->update(['movies' => $movies, 'graveyard' => $graveyard, 'votes' => $votes]);

                    $this->broadcast($token, 'veto_threshold', $name, $userId, $movie['title'] ?? $movie['name'] ?? '', [
                        'movieId' => $movieId,
                        'movie'   => $this->slim($movie),
                        'votes'   => $votes,
                    ]);
                    return response()->json(['ok' => true]);
                }
            }

            $batch->update(['votes' => $votes]);
            $movieTitle = collect($batch->movies)->firstWhere('id', $movieId)['title']
                ?? collect($batch->movies)->firstWhere('id', $movieId)['name'] ?? '';
            $direction  = $removing ? 'off' : 'on';

            $this->broadcast($token, "vote_veto_{$direction}", $name, $userId, $movieTitle, [
                'movieId' => $movieId,
                'voters'  => $voters,
            ]);

        } else {
            $restoreVotes = $batch->restore_votes ?? [];
            $voters       = $restoreVotes[$key] ?? [];
            $removing     = in_array($userId, $voters);

            $voters             = $removing
                ? array_values(array_diff($voters, [$userId]))
                : [...$voters, $userId];
            $restoreVotes[$key] = $voters;

            if (count($voters) >= $this->vetoThreshold($pCount)) {
                $movie = collect($batch->graveyard ?? [])->first(fn($m) => $m['id'] === $movieId);
                if ($movie) {
                    $movies    = [...$batch->movies, $movie];
                    $graveyard = collect($batch->graveyard ?? [])->reject(fn($m) => $m['id'] === $movieId)->values()->all();
                    unset($restoreVotes[$key]);
                    $batch->update(['movies' => $movies, 'graveyard' => $graveyard, 'restore_votes' => $restoreVotes]);

                    $this->broadcast($token, 'restore_threshold', $name, $userId, $movie['title'] ?? $movie['name'] ?? '', [
                        'movieId'      => $movieId,
                        'movie'        => $this->slim($movie),
                        'restoreVotes' => $restoreVotes,
                    ]);
                    return response()->json(['ok' => true]);
                }
            }

            $batch->update(['restore_votes' => $restoreVotes]);
            $movieTitle = collect($batch->graveyard ?? [])->firstWhere('id', $movieId)['title']
                ?? collect($batch->graveyard ?? [])->firstWhere('id', $movieId)['name'] ?? '';
            $direction  = $removing ? 'off' : 'on';

            $this->broadcast($token, "vote_restore_{$direction}", $name, $userId, $movieTitle, [
                'movieId' => $movieId,
                'voters'  => $voters,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    // ── Ready to Roll ─────────────────────────────────────────────────

    public function toggleReady(Request $request, string $token)
    {
        $batch  = $this->guardBatch($token);
        $userId = $request->input('userId');
        $name   = $request->input('name', 'Someone');
        $pCount = $this->participantCount($batch);

        $ready = $batch->ready ?? [];
        if (in_array($userId, $ready)) {
            $ready = array_values(array_diff($ready, [$userId]));
        } else {
            $ready[] = $userId;
        }
        $batch->update(['ready' => $ready]);

        if (count($ready) >= $this->readyThreshold($pCount) && count($batch->movies) > 0) {
            $winner = $batch->movies[array_rand($batch->movies)];
            $this->broadcast($token, 'rolled', $name, $userId, '', ['winner' => $this->slim($winner)]);
            return response()->json(['ok' => true, 'rolled' => true, 'winner' => $winner]);
        }

        $isReady = in_array($userId, $ready);
        $this->broadcast($token, $isReady ? 'ready_on' : 'ready_off', $name, $userId, '', ['ready' => $ready]);
        return response()->json(['ok' => true, 'rolled' => false]);
    }

    // ── Try Again — vote-based, requires all participants ────────────

    public function restart(Request $request, string $token)
    {
        $batch  = $this->guardBatch($token);
        $userId = $request->input('userId');
        $name   = $request->input('name', 'Someone');
        $pCount = $this->participantCount($batch);

        $votes = $batch->try_again_votes ?? [];

        if (in_array($userId, $votes)) {
            $votes = array_values(array_diff($votes, [$userId]));
            $batch->update(['try_again_votes' => $votes]);
            $this->broadcast($token, 'try_again_off', $name, $userId, '', ['tryAgainVotes' => $votes, 'needed' => $pCount]);
            return response()->json(['ok' => true]);
        }

        $votes[] = $userId;
        $batch->update(['try_again_votes' => $votes]);

        if (count($votes) >= $pCount) {
            $movies = array_merge($batch->movies, $batch->graveyard ?? []);
            $batch->update([
                'movies'          => $movies,
                'graveyard'       => [],
                'votes'           => [],
                'restore_votes'   => [],
                'ready'           => [],
                'try_again_votes' => [],
            ]);
            $this->broadcast($token, 'restarted', $name, $userId, '', ['movies' => $this->slimAll($movies)]);
            return response()->json(['ok' => true, 'restarted' => true]);
        }

        $this->broadcast($token, 'try_again_on', $name, $userId, '', ['tryAgainVotes' => $votes, 'needed' => $pCount]);
        return response()->json(['ok' => true]);
    }

    // ── Roll New Batch ────────────────────────────────────────────────

    public function toggleRefreshVote(Request $request, string $token, MovieService $movieService, TmdbClient $tmdb)
    {
        $batch  = $this->guardBatch($token);
        $userId = $request->input('userId');
        $name   = $request->input('name', 'Someone');
        $pCount = $this->participantCount($batch);

        $refreshVotes = $batch->refresh_votes ?? [];
        if (in_array($userId, $refreshVotes)) {
            $refreshVotes = array_values(array_diff($refreshVotes, [$userId]));
            $batch->update(['refresh_votes' => $refreshVotes]);
            $this->broadcast($token, 'refresh_off', $name, $userId, '', ['refreshVotes' => $refreshVotes]);
            return response()->json(['ok' => true, 'refreshed' => false]);
        }

        $refreshVotes[] = $userId;
        $batch->update(['refresh_votes' => $refreshVotes]);

        if (count($refreshVotes) >= $this->refreshThreshold($pCount) && $batch->criteria) {
            $criteria = $batch->criteria;
            $isTv     = $batch->media_type === 'tv';
            $country  = $movieService->getUserCountry();

            try {
                if ($isTv) {
                    $criteria['page'] = $movieService->resolvePage($tmdb, $criteria, $country, 'tv');
                    $results = $tmdb->discoverTv($criteria, $country);
                    $movies  = $movieService->pickBatch($movieService->normaliseShows($results['results'] ?? []));
                } else {
                    $criteria['page'] = $movieService->resolvePage($tmdb, $criteria, $country);
                    $results = $tmdb->discover($criteria, $country);
                    $movies  = $movieService->pickBatch($results['results'] ?? []);
                }

                $batch->update([
                    'movies'        => $movies,
                    'graveyard'     => [],
                    'votes'         => [],
                    'restore_votes' => [],
                    'ready'         => [],
                    'refresh_votes' => [],
                ]);

                $this->broadcast($token, 'refreshed', $name, $userId, '', ['movies' => $this->slimAll($movies)]);
                return response()->json(['ok' => true, 'refreshed' => true]);
            } catch (\Throwable) {
                $batch->update(['refresh_votes' => []]);
            }
        }

        $this->broadcast($token, 'refresh_on', $name, $userId, '', ['refreshVotes' => $refreshVotes]);
        return response()->json(['ok' => true, 'refreshed' => false]);
    }
}
