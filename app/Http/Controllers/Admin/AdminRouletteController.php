<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Roulette;
use App\Models\Setting;
use App\Services\RouletteTagMapper;
use App\Services\TmdbClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminRouletteController extends Controller
{
    public function index(Request $request)
    {
        $q         = $request->input('q');
        $mediaType = $request->input('type', 'movie');

        $roulettes = Roulette::where('is_system', true)
            ->where('media_type', $mediaType)
            ->when($q, fn($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $movieDefault = ['By Decade', 'Netflix', 'Prime Video', 'HBO', 'Disney+', 'Apple TV+', 'World Cinema', 'Anime', 'Community', 'By Genre'];
        $tvDefault    = ['By Decade', 'Netflix', 'Prime Video', 'HBO', 'Disney+', 'Apple TV+', 'World TV', 'Anime', 'By Genre'];

        $rowOrder = $mediaType === 'tv'
            ? Setting::get('roulette_tv_row_order', $tvDefault)
            : Setting::get('roulette_row_order', $movieDefault);

        $grouped = $roulettes->groupBy(fn(Roulette $r) => $r->groupName());

        $ordered = collect($rowOrder)
            ->mapWithKeys(fn($g) => [$g => $grouped->get($g, collect())]);

        $ungrouped = collect();
        foreach ($grouped as $name => $items) {
            if (!in_array($name, $rowOrder)) {
                $ungrouped = $ungrouped->merge($items);
            }
        }
        if ($ungrouped->isNotEmpty()) {
            $ordered->put('Ungrouped', $ungrouped);
        }

        return view('admin.roulettes.index', compact('ordered', 'q', 'mediaType'));
    }

    public function create()
    {
        $rowOrder = $this->allRowNames();
        return view('admin.roulettes.form', ['roulette' => null, 'rowOrder' => $rowOrder]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Roulette::create($data);

        return redirect()->route('admin.roulettes.index')->with('success', 'Roulette created.');
    }

    public function edit(Roulette $roulette)
    {
        $rowOrder = $this->allRowNames();
        return view('admin.roulettes.form', compact('roulette', 'rowOrder'));
    }

    public function update(Request $request, Roulette $roulette)
    {
        $data = $this->validated($request, $roulette->id);
        $roulette->update($data);

        return redirect()->route('admin.roulettes.index')->with('success', 'Roulette updated.');
    }

    public function destroy(Roulette $roulette)
    {
        $roulette->delete();
        return redirect()->route('admin.roulettes.index')->with('success', 'Roulette deleted.');
    }

    public function togglePublic(Roulette $roulette)
    {
        $roulette->update(['is_public' => !$roulette->is_public]);
        return redirect()->back()->with('success', $roulette->name . ' is now ' . ($roulette->is_public ? 'public' : 'hidden') . '.');
    }

    public function refreshPoster(Roulette $roulette, TmdbClient $tmdb, Request $request): \Illuminate\Http\JsonResponse
    {
        // Selecting a specific poster from the grid — just move it to front
        if ($path = $request->input('path')) {
            $current   = $roulette->poster_paths ?? [];
            $reordered = array_values(array_merge([$path], array_filter($current, fn($p) => $p !== $path)));
            $roulette->update(['poster_paths' => $reordered]);
            return response()->json(['poster_path' => $path, 'all_paths' => $reordered]);
        }

        // Fetch a fresh batch from TMDB
        $mapper         = new RouletteTagMapper();
        $criteria = $roulette->media_type === 'tv'
            ? $mapper->toCriteriaTv($roulette->tags ?? [])
            : $mapper->toCriteria($roulette->tags ?? []);
        $criteria['sort_by'] = 'popularity.desc';
        $criteria['page']    = rand(1, 5);

        try {
            $results = $roulette->media_type === 'tv'
                ? $tmdb->discoverTv($criteria, 'US')
                : $tmdb->discover($criteria, 'US');

            if ($roulette->media_type === 'tv' && empty($results['results']) && isset($criteria['with_genres'])) {
                $results = $tmdb->discoverTv(array_diff_key($criteria, ['with_genres' => true]), 'US');
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

            return response()->json(['poster_path' => $paths[0] ?? null, 'all_paths' => $paths]);
        } catch (\Throwable) {
            return response()->json(['poster_path' => null, 'all_paths' => []], 422);
        }
    }

    public function reorder(Request $request)
    {
        $items = $request->validate(['items' => 'required|array', 'items.*.id' => 'required|integer', 'items.*.sort_order' => 'required|integer'])['items'];

        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                Roulette::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
            }
        });

        return response()->json(['ok' => true]);
    }

    private function allRowNames(): array
    {
        $movieDefault = ['By Decade', 'Netflix', 'Prime Video', 'HBO', 'Disney+', 'Apple TV+', 'World Cinema', 'Anime', 'Community', 'By Genre'];
        $tvDefault    = ['By Decade', 'Netflix', 'Prime Video', 'HBO', 'Disney+', 'Apple TV+', 'World TV', 'Anime', 'By Genre'];

        return array_unique(array_merge(
            Setting::get('roulette_row_order', $movieDefault),
            Setting::get('roulette_tv_row_order', $tvDefault)
        ));
    }

    private function validated(Request $request, ?int $excludeId = null): array
    {
        $request->validate([
            'name'        => 'required|string|max:80',
            'description' => 'nullable|string|max:500',
            'is_public'   => 'boolean',
        ]);

        $tags = $this->buildTags($request);

        if (empty($tags)) {
            return back()->withErrors(['tags' => 'At least one tag must be set.'])->withInput();
        }

        $fingerprint = Roulette::fingerprintFromTags($tags);

        // Fingerprint must be unique among system roulettes only
        $duplicate = Roulette::where('tag_fingerprint', $fingerprint)
            ->where('is_system', true)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['tags' => 'A system roulette with this exact tag combination already exists.'])->withInput();
        }

        $request->validate([
            'slug' => "required|string|max:100|unique:roulettes,slug,{$excludeId}",
        ]);

        return [
            'name'            => $request->input('name'),
            'slug'            => $request->input('slug'),
            'description'     => $request->input('description'),
            'tags'            => $tags,
            'tag_fingerprint' => $fingerprint,
            'is_public'       => $request->boolean('is_public'),
            'is_system'       => $request->boolean('is_system', false),
            'row'             => $request->input('row') ?: null,
            'media_type'      => $request->input('media_type') === 'tv' ? 'tv' : 'movie',
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
