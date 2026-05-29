# Collaborative Batch — Real-Time Movie Selection

A shared batch session where a group of people eliminate movies in real time until one is left. Everyone who has the link open sees removals happen live with animations.

---

## Concept

1. User rolls a batch → gets a shareable link with a short room token (e.g. `/batch/collab/abc123`)
2. Friends open the link → everyone joins the same "room"
3. Anyone can veto a movie → it animates off everyone's screen simultaneously with "[Name] vetoed this"
4. Last movie standing → celebration animation → everyone sees the winner

---

## Architecture Decisions

### Token
- Short random ID (8 chars, e.g. `Str::random(8)`) — NOT base64-encoded content
- Stored in DB, URL is just a reference
- Replaces the current base64 URL approach for collaborative batches
- Keep the existing `/batch/share/{token}` for non-collaborative shares (read-only, no DB)

### Real-Time
- **Laravel Reverb** — free, open source, runs on your own server
- No external service, no cost beyond server resources
- Each room = one Reverb channel: `batch.{token}`
- Events broadcast: `MovieVetoed`, `BatchComplete`

### State Storage
- DB table `collab_batches` stores current movie list as JSON
- Each veto updates the JSON in DB and broadcasts to channel
- Expires after 24 hours (or when session ends)

---

## Database

```php
Schema::create('collab_batches', function (Blueprint $table) {
    $table->string('token', 8)->primary();
    $table->json('movies');            // current remaining movies
    $table->string('media_type', 10)->default('movie');
    $table->unsignedBigInteger('created_by')->nullable(); // user_id
    $table->timestamp('expires_at');
    $table->timestamps();

    $table->index('expires_at');       // for cleanup job
});
```

---

## User Identity

| Situation | Display |
|-----------|---------|
| Logged in | Their name from `auth()->user()->name` |
| Anonymous | Fun random name generated once per session, stored in `localStorage` (e.g. "Blue Fox", "Red Panda") — pick from a list of colors + animals |

Store anonymous identity: `localStorage.setItem('collab_identity', 'Blue Fox')`

---

## Reverb Setup

```bash
composer require laravel/reverb
php artisan reverb:install
```

Add to `.env`:
```
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
```

Run the WebSocket server:
```bash
php artisan reverb:start
```

On production — run as a background process via supervisor:
```ini
[program:reverb]
command=php /var/www/moviepicker/artisan reverb:start
autostart=true
autorestart=true
```

---

## Events

### `MovieVetoed`
Broadcast on channel `batch.{token}` when a movie is removed.

```php
class MovieVetoed implements ShouldBroadcast
{
    public function __construct(
        public string $token,
        public int    $movieId,
        public string $vetoedBy,   // display name
        public array  $remaining,  // movies left after veto
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('batch.' . $this->token);
    }
}
```

### `BatchComplete`
Broadcast when only one movie remains.

```php
class BatchComplete implements ShouldBroadcast
{
    public function __construct(
        public string $token,
        public array  $winner,     // the last movie
        public string $decidedBy,
    ) {}
}
```

---

## Routes

```php
Route::get('/batch/collab/{token}',          [CollabBatchController::class, 'show']);
Route::post('/batch/collab',                  [CollabBatchController::class, 'create']);
Route::delete('/batch/collab/{token}/{id}',   [CollabBatchController::class, 'veto']);
```

---

## Controller Sketch

```php
class CollabBatchController extends Controller
{
    public function create(Request $request)
    {
        // Called when user clicks "Collaborative Share" on a batch page
        // $request->movies = array from current batch
        $batch = CollabBatch::create([
            'token'      => Str::random(8),
            'movies'     => $request->movies,
            'media_type' => $request->media_type ?? 'movie',
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
        $batch = CollabBatch::where('token', $token)->firstOrFail();

        $movies = collect($batch->movies)->reject(fn($m) => $m['id'] === $movieId)->values()->all();
        $batch->update(['movies' => $movies]);

        $vetoedBy = auth()->user()?->name ?? $request->input('identity', 'Someone');

        if (count($movies) === 1) {
            broadcast(new BatchComplete($token, $movies[0], $vetoedBy));
        } else {
            broadcast(new MovieVetoed($token, $movieId, $vetoedBy, $movies));
        }

        return response()->json(['remaining' => count($movies)]);
    }
}
```

---

## Frontend

### JS — Joining a channel
```js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
});

Echo.channel(`batch.${token}`)
    .listen('MovieVetoed', (e) => {
        animateVeto(e.movieId, e.vetoedBy);
        updateRemainingCards(e.remaining);
    })
    .listen('BatchComplete', (e) => {
        animateWinner(e.winner, e.decidedBy);
    });
```

### Veto Animation Ideas
- Card shakes briefly, then slides/fades out
- Small toast appears: *"Red Panda vetoed Inception"*
- If you're the one vetoing: instant feedback, card disappears immediately
- For others: 300ms delay then same animation (feels live)

### Winner Animation
- Confetti burst
- Card scales up to center of screen
- *"You all picked [Movie]! 🎉"*
- Button to navigate to the movie detail page

---

## UI Changes Needed

1. **Batch page** — add a "Collaborative Share" button alongside the existing share button
   - Regular share = current base64 URL (read-only, works forever)
   - Collaborative share = creates a DB session, gives a room link
2. **Collab batch page** — show participant count ("3 people in this session")
3. **Each movie card** — ✕ veto button (visible to all, any participant can use it)
4. **Veto counter** — optional: show how many movies have been eliminated ("7 of 10 eliminated")

---

## Cleanup

Add a scheduled command to delete expired sessions:

```php
// In routes/console.php or a command
Schedule::command('collab:cleanup')->daily();

// Command:
CollabBatch::where('expires_at', '<', now())->delete();
```

---

## Implementation Order

1. Install and configure Reverb
2. Create `collab_batches` migration and model
3. Create `CollabBatchController`
4. Create broadcast events (`MovieVetoed`, `BatchComplete`)
5. Add route for collab batch page
6. Build `batch/collab.blade.php` view (can reuse batch layout)
7. Write JS for Echo channel + veto animation
8. Add "Collaborative Share" button to batch page
9. Test with two browser tabs
10. Deploy Reverb as a supervisor process on production

---

## Notes

- Keep existing `/batch/share/{token}` (base64, read-only) as-is — it requires no server state and links never expire. Collaborative batches are a separate flow.
- The room expires after 24h. After expiry, the link shows a "session ended" page.
- Anonymous identity (fun names) makes it feel playful — don't force login to participate.
- Consider a "lock" feature later: creator can prevent new people from joining mid-session.
