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
                $criteria         = $isTv ? $mapper->toCriteriaTv($roulette->tags) : $mapper->toCriteriaMovie($roulette->tags);
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
        if (Roulette::where('user_id', auth()->id())->count() >= 100) {
            return back()->withErrors(['limit' => 'You can have at most 100 roulettes.'])->withInput();
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

        // Fetch a page of posters from TMDB
        $page   = max(1, min(10, (int) $request->input('page', 1)));
        $sort   = $request->input('sort', 'rating') === 'rating' ? 'rating' : 'popularity';
        $mapper = new RouletteTagMapper();
        $isTv   = $roulette->media_type === 'tv';
        $rawTags  = $request->input('tags');
        $tags     = $rawTags !== null ? $mapper->normalizeTags($rawTags) : ($roulette->tags ?? []);
        $criteria = $isTv ? $mapper->toCriteriaTv($tags) : $mapper->toCriteriaMovie($tags);
        $criteria['sort_by'] = $sort === 'rating' ? 'vote_average.desc' : 'popularity.desc';
        $criteria['page']    = $page;
        if ($sort === 'rating') {
            $criteria['vote_count.gte'] = 50;
        }

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

            if ($paths && $page === 1) {
                $roulette->update(['poster_paths' => $paths]);
            }

            $totalPages = min(10, $results['total_pages'] ?? 1);

            return response()->json([
                'poster_path' => $paths[0] ?? null,
                'all_paths'   => $paths,
                'page'        => $page,
                'total_pages' => $totalPages,
                'fallback'    => $usedFallback,
            ]);
        } catch (\Throwable) {
            return response()->json(['poster_path' => null, 'all_paths' => []], 422);
        }
    }

    public function fromCriteria(Request $request)
    {
        if (Roulette::where('user_id', auth()->id())->count() >= 100) {
            return back()->with('error', 'You can have at most 100 roulettes.');
        }

        $request->validate(['name' => 'required|string|max:80']);

        $mediaType  = $request->input('media_type') === 'tv' ? 'tv' : 'movie';
        $sessionKey = $mediaType === 'tv' ? 'tvInput' : 'userInput';
        $criteria   = array_diff_key(
            session($sessionKey, []),
            array_flip(['total_pages', 'page'])
        );

        $mapper = new RouletteTagMapper();
        $tags   = $mapper->criteriaToTags($criteria, $mediaType);

        if (empty($tags)) {
            return back()->with('error', 'No criteria to save as a roulette.');
        }

        $fingerprint = ($mediaType === 'tv' ? 'tv:' : '') . Roulette::fingerprintFromTags($tags);
        $existing    = Roulette::where('tag_fingerprint', $fingerprint)->whereNotNull('poster_paths')->first();

        Roulette::create([
            'user_id'         => auth()->id(),
            'is_system'       => false,
            'name'            => $request->input('name'),
            'slug'            => Roulette::generateSlug($request->input('name')),
            'description'     => null,
            'tags'            => $tags,
            'tag_fingerprint' => $fingerprint,
            'is_public'       => $request->boolean('is_public'),
            'row'             => null,
            'media_type'      => $mediaType,
            'poster_paths'    => $existing?->poster_paths,
        ]);

        return redirect()->route('my-roulettes.manage')->with('success', '"' . $request->input('name') . '" saved to My Roulettes.');
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
        if ($withoutGenres = $request->input('tags.without_genre', [])) {
            $tags['without_genre'] = array_values($withoutGenres);
        }
        $yearFrom = $request->input('tags.year_from');
        $yearTo   = $request->input('tags.year_to');
        if ($yearFrom !== null && $yearFrom !== '') $tags['year_from'] = (int) $yearFrom;
        if ($yearTo   !== null && $yearTo   !== '') $tags['year_to']   = (int) $yearTo;
        if ($country = $request->input('tags.country')) {
            $tags['country'] = [$country];
        }
        if ($language = $request->input('tags.language')) {
            $tags['language'] = [$language];
        }
        if ($withCast = array_filter($request->input('tags.with_cast', []))) {
            $tags['with_cast'] = array_values(array_map('strval', $withCast));
        }
        if ($withCrew = array_filter($request->input('tags.with_crew', []))) {
            $tags['with_crew'] = array_values(array_map('strval', $withCrew));
        }
        $voteGte   = $request->input('tags.vote_average_gte');
        $voteLte   = $request->input('tags.vote_average_lte');
        $voteCount = $request->input('tags.vote_count_gte');
        if ($voteGte   !== null && $voteGte   !== '') $tags['vote_average_gte'] = (float) $voteGte;
        if ($voteLte   !== null && $voteLte   !== '') $tags['vote_average_lte'] = (float) $voteLte;
        if ($voteCount !== null && $voteCount !== '') $tags['vote_count_gte']   = (int)   $voteCount;
        return $tags;
    }
}
