<?php

namespace App\Http\Controllers;

use App\Models\Roulette;
use App\Models\Setting;
use App\Services\MovieService;
use App\Services\TmdbClient;
use App\Services\RouletteTagMapper;
use Illuminate\Http\Request;

class UserRouletteController extends Controller
{
    public function __construct(private MovieService $movieService) {}

    private function rowOrderKey(): string
    {
        return 'user_row_order.' . auth()->id();
    }

    private function userRowOrder(): array
    {
        return Setting::get($this->rowOrderKey(), []);
    }

    public function index(TmdbClient $tmdb)
    {
        $roulettes = Roulette::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        $mapper = new RouletteTagMapper();
        foreach ($roulettes->whereNull('poster_paths') as $roulette) {
            // Check if another roulette with same tags already has posters
            $existing = Roulette::where('tag_fingerprint', $roulette->tag_fingerprint)
                ->whereNotNull('poster_paths')
                ->first();

            if ($existing) {
                $roulette->update(['poster_paths' => $existing->poster_paths]);
                continue;
            }

            try {
                $isTv             = $roulette->media_type === 'tv';
                $criteria         = $isTv ? $mapper->toCriteriaTv($roulette->tags) : $mapper->toCriteria($roulette->tags);
                $criteria['page'] = 1;
                $results          = $isTv ? $tmdb->discoverTv($criteria, 'US') : $tmdb->discover($criteria, 'US');
                $paths            = [];
                foreach ($results['results'] ?? [] as $item) {
                    if (!empty($item['poster_path'])) {
                        $paths[] = $item['poster_path'];
                        }
                }
                $roulette->update(['poster_paths' => $paths ?: null]);
            } catch (\Throwable) {}
        }

        $rowOrder = $this->userRowOrder();

        $grouped = $roulettes->groupBy(fn(Roulette $r) => $r->row ?? 'Uncategorised');

        $ordered = collect($rowOrder)
            ->mapWithKeys(fn($g) => [$g => $grouped->get($g, collect())]);

        // Roulettes with no row or a deleted row go to Uncategorised
        $ungrouped = collect();
        foreach ($grouped as $name => $items) {
            if (!in_array($name, $rowOrder)) {
                $ungrouped = $ungrouped->merge($items);
            }
        }
        if ($ungrouped->isNotEmpty()) {
            $ordered->put('Uncategorised', $ungrouped);
        }

        return view('my-roulettes.index', compact('ordered', 'roulettes'));
    }

    public function manage()
    {
        $roulettes = Roulette::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        $rowOrder = $this->userRowOrder();

        $grouped = $roulettes->groupBy(fn(Roulette $r) => $r->row ?? 'Uncategorised');

        $ordered = collect($rowOrder)
            ->mapWithKeys(fn($g) => [$g => $grouped->get($g, collect())]);

        $ungrouped = collect();
        foreach ($grouped as $name => $items) {
            if (!in_array($name, $rowOrder)) {
                $ungrouped = $ungrouped->merge($items);
            }
        }
        if ($ungrouped->isNotEmpty()) {
            $ordered->put('Uncategorised', $ungrouped);
        }

        return view('my-roulettes.manage', compact('ordered', 'rowOrder'));
    }

    public function create()
    {
        $rowOrder = $this->userRowOrder();
        return view('my-roulettes.form', ['roulette' => null, 'rowOrder' => $rowOrder]);
    }

    public function store(Request $request)
    {
        if (Roulette::where('user_id', auth()->id())->count() >= 20) {
            return back()->withErrors(['limit' => 'You can have at most 20 roulettes.'])->withInput();
        }

        $data = $this->validated($request);
        $data['user_id']   = auth()->id();
        $data['is_system'] = false;

        // Reuse poster_paths from any existing roulette with the same tags
        $existing = Roulette::where('tag_fingerprint', $data['tag_fingerprint'])
            ->whereNotNull('poster_paths')
            ->first();
        if ($existing) {
            $data['poster_paths'] = $existing->poster_paths;
        }

        Roulette::create($data);

        return redirect()->route('my-roulettes.manage')->with('success', 'Roulette created.');
    }

    public function edit(Roulette $roulette)
    {
        abort_if($roulette->user_id !== auth()->id(), 403);
        $rowOrder = $this->userRowOrder();
        return view('my-roulettes.form', compact('roulette', 'rowOrder'));
    }

    public function update(Request $request, Roulette $roulette)
    {
        abort_if($roulette->user_id !== auth()->id(), 403);
        $data = $this->validated($request, $roulette->id);

        // Reuse poster_paths if tags changed and a match already exists
        if ($data['tag_fingerprint'] !== $roulette->tag_fingerprint) {
            $existing = Roulette::where('tag_fingerprint', $data['tag_fingerprint'])
                ->whereNotNull('poster_paths')
                ->first();
            if ($existing) {
                $data['poster_paths'] = $existing->poster_paths;
            }
        }

        $roulette->update($data);
        return redirect()->route('my-roulettes.manage')->with('success', 'Roulette updated.');
    }

    public function destroy(Roulette $roulette)
    {
        abort_if($roulette->user_id !== auth()->id(), 403);
        $roulette->delete();
        return redirect()->route('my-roulettes.manage')->with('success', 'Roulette deleted.');
    }

    public function togglePublic(Roulette $roulette)
    {
        abort_if($roulette->user_id !== auth()->id(), 403);
        $roulette->update(['is_public' => !$roulette->is_public]);
        return redirect()->back()->with('success', $roulette->name . ' is now ' . ($roulette->is_public ? 'public' : 'private') . '.');
    }

    public function refreshPoster(Roulette $roulette, TmdbClient $tmdb, Request $request): \Illuminate\Http\JsonResponse
    {
        abort_if($roulette->user_id !== auth()->id(), 403);

        // Selecting a specific poster from the grid — just move it to front
        if ($path = $request->input('path')) {
            $current   = $roulette->poster_paths ?? [];
            $reordered = array_values(array_merge([$path], array_filter($current, fn($p) => $p !== $path)));
            $roulette->update(['poster_paths' => $reordered]);
            return response()->json(['poster_path' => $path, 'all_paths' => $reordered]);
        }

        // Fetch a fresh batch from TMDB
        $mapper         = new RouletteTagMapper();
        $isTv     = $roulette->media_type === 'tv';
        $criteria = $isTv ? $mapper->toCriteriaTv($roulette->tags ?? []) : $mapper->toCriteria($roulette->tags ?? []);
        $criteria['sort_by'] = 'popularity.desc';
        $criteria['page']    = rand(1, 5);

        $country      = $this->movieService->getUserCountry();
        $usedFallback = false;

        try {
            $results = $isTv ? $tmdb->discoverTv($criteria, $country) : $tmdb->discover($criteria, $country);

            if (empty($results['results']) && isset($criteria['with_watch_providers'])) {
                $usedFallback = true;
                $fallback     = array_diff_key($criteria, ['with_watch_providers' => true]);
                $results      = $isTv ? $tmdb->discoverTv($fallback, $country) : $tmdb->discover($fallback, $country);
            }

            $paths = [];
            foreach ($results['results'] ?? [] as $item) {
                if (!empty($item['poster_path'])) {
                    $paths[] = $item['poster_path'];
                }
            }

            if ($paths) {
                $roulette->update(['poster_paths' => $paths]);
            }

            return response()->json(['poster_path' => $paths[0] ?? null, 'all_paths' => $paths, 'fallback' => $usedFallback]);
        } catch (\Throwable) {
            return response()->json(['poster_path' => null, 'all_paths' => []], 422);
        }
    }

    public function reorderRows(Request $request)
    {
        $rows = $request->validate(['rows' => 'required|array', 'rows.*' => 'string'])['rows'];
        Setting::set($this->rowOrderKey(), array_values($rows));
        return response()->json(['ok' => true]);
    }


    private function validated(Request $request, ?int $excludeId = null): array
    {
        $request->validate([
            'name' => 'required|string|max:80',
        ]);

        $tags = $this->buildTags($request);

        if (empty($tags)) {
            return back()->withErrors(['tags' => 'At least one tag must be set.'])->withInput();
        }

        $mediaType   = $request->input('media_type') === 'tv' ? 'tv' : 'movie';
        $fingerprint = ($mediaType === 'tv' ? 'tv:' : '') . Roulette::fingerprintFromTags($tags);

        return [
            'name'            => $request->input('name'),
            'slug'            => Roulette::generateSlug($request->input('name'), $excludeId),
            'description'     => $request->input('description'),
            'tags'            => $tags,
            'tag_fingerprint' => $fingerprint,
            'is_public'       => $request->boolean('is_public'),
            'row'             => $request->input('row') ?: null,
            'media_type'      => $mediaType,
        ];
    }

    private function buildTags(Request $request): array
    {
        $tags = [];
        if ($platform = $request->input('tags.platform')) {
            $tags['platform'] = [$platform];
        }
        if ($genres = $request->input('tags.genre', [])) {
            $tags['genre'] = array_values($genres);
        }
        if ($era = $request->input('tags.era')) {
            $tags['era'] = [$era];
        }
        if ($language = $request->input('tags.language')) {
            $tags['language'] = [$language];
        }
        return $tags;
    }
}
