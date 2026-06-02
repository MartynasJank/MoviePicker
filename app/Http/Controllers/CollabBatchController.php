<?php

namespace App\Http\Controllers;

use App\Events\CollabStateUpdated;
use App\Models\CollabBatch;
use App\Services\MovieService;
use App\Services\TmdbClient;
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
        return CollabBatch::where('token', $token)
            ->where('expires_at', '>', now())
            ->firstOrFail();
    }

    // ── Create ────────────────────────────────────────────────────────

    public function create(Request $request)
    {
        $request->validate([
            'movies'     => 'required|array|min:2',
            'media_type' => 'in:movie,tv,mixed',
        ]);

        $sessionKey = $request->input('media_type') === 'tv' ? 'tvInput' : 'userInput';
        $movies     = collect($request->movies)->shuffle()->take(100)->values()->all();

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
        return view('batch.collab', compact('batch'));
    }

    // ── Participant tracking ──────────────────────────────────────────

    public function join(Request $request, string $token)
    {
        $batch  = $this->guardBatch($token);
        $userId = $request->input('userId');
        $name   = $request->input('name', 'Anonymous');

        $participants = collect($batch->participants ?? [])
            ->reject(fn($p) => $p['userId'] === $userId)
            ->push(['userId' => $userId, 'name' => $name, 'last_seen' => now()->toISOString()])
            ->values()
            ->all();

        $batch->update(['participants' => $participants]);

        $participant = collect($participants)->firstWhere('userId', $userId);
        $this->broadcast($token, 'join', $name, $userId, '', ['participant' => $participant]);

        return response()->json(['ok' => true]);
    }

    public function leave(Request $request, string $token)
    {
        $batch  = CollabBatch::where('token', $token)->first();
        if (!$batch) return response()->json(['ok' => true]);

        $userId = $request->input('userId');
        $name   = collect($batch->participants ?? [])->firstWhere('userId', $userId)['name'] ?? '';

        $participants = collect($batch->participants ?? [])
            ->reject(fn($p) => $p['userId'] === $userId)
            ->values()
            ->all();

        $batch->update([
            'participants'  => $participants,
            'ready'         => array_values(array_diff($batch->ready ?? [], [$userId])),
            'refresh_votes' => array_values(array_diff($batch->refresh_votes ?? [], [$userId])),
        ]);

        $this->broadcast($token, 'leave', $name, $userId);
        return response()->json(['ok' => true]);
    }

    public function removeVotes(Request $request, string $token)
    {
        $batch = CollabBatch::where('token', $token)->first();
        if (!$batch) return response()->json(['ok' => true]);

        $targetUserId = $request->input('targetUserId');

        $votes = collect($batch->votes ?? [])
            ->map(fn($voters) => array_values(array_diff($voters, [$targetUserId])))
            ->all();

        $restoreVotes = collect($batch->restore_votes ?? [])
            ->map(fn($voters) => array_values(array_diff($voters, [$targetUserId])))
            ->all();

        $batch->update(['votes' => $votes, 'restore_votes' => $restoreVotes]);

        $this->broadcast($token, 'vote_cleanup', '', '', '', [
            'votes'        => $votes,
            'restoreVotes' => $restoreVotes,
        ]);

        return response()->json(['ok' => true]);
    }

    public function heartbeat(Request $request, string $token)
    {
        $batch   = $this->guardBatch($token);
        $userId  = $request->input('userId');
        $name    = $request->input('name', 'Anonymous');
        $oldName = collect($batch->participants ?? [])->firstWhere('userId', $userId)['name'] ?? null;

        $participants = collect($batch->participants ?? [])
            ->map(fn($p) => $p['userId'] === $userId
                ? array_merge($p, ['name' => $name, 'last_seen' => now()->toISOString()])
                : $p)
            ->values()
            ->all();

        if (!collect($participants)->contains('userId', $userId)) {
            $participants[] = ['userId' => $userId, 'name' => $name, 'last_seen' => now()->toISOString()];
        }

        $batch->update(['participants' => $participants]);

        $participant = collect($participants)->firstWhere('userId', $userId);

        if ($oldName !== null && $oldName !== $name) {
            $this->broadcast($token, 'rename', $name, $userId, $oldName, ['participant' => $participant]);
        } elseif ($oldName === null) {
            $this->broadcast($token, 'join', $name, $userId, '', ['participant' => $participant]);
        }

        return response()->json(['ok' => true]);
    }

    // ── Voting ────────────────────────────────────────────────────────

    public function vote(Request $request, string $token, int $movieId)
    {
        $batch   = $this->guardBatch($token);
        $userId  = $request->input('userId');
        $name    = $request->input('name', 'Someone');
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
