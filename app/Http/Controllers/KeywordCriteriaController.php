<?php

namespace App\Http\Controllers;

use App\Services\TmdbClient;
use App\Services\MovieService;
use Illuminate\Http\RedirectResponse;

class KeywordCriteriaController extends Controller
{
    public function movie(int $id, string $name, TmdbClient $tmdb, MovieService $movieService): RedirectResponse
    {
        $this->mergeKeyword('userInput', $id, $name);

        $country  = $movieService->getUserCountry();
        $criteria = session('roll_source') === 'criteria'
            ? session('userInput', [])
            : ['with_keywords' => [$id], 'with_keywords_names' => [$name]];

        $criteria['page'] = $movieService->resolvePage($tmdb, $criteria, $country);
        $results  = $tmdb->discover($criteria, $country);

        if (empty($results['results'])) {
            return $this->noResults('movie');
        }

        session(['roll_source' => 'criteria']);
        return redirect()->route('movie', [$movieService->pickRandom($results['results'])['id']]);
    }

    public function tv(int $id, string $name, TmdbClient $tmdb, MovieService $movieService): RedirectResponse
    {
        $this->mergeKeyword('tvInput', $id, $name);

        $country  = $movieService->getUserCountry();
        $criteria = session('roll_source') === 'criteria'
            ? session('tvInput', [])
            : ['with_keywords' => [$id], 'with_keywords_names' => [$name]];

        $criteria['page'] = $movieService->resolvePage($tmdb, $criteria, $country, 'tv');
        $results  = $tmdb->discoverTv($criteria, $country);

        if (empty($results['results'])) {
            return $this->noResults('tv');
        }

        session(['roll_source' => 'criteria']);
        return redirect()->route('tv.show', [$movieService->pickRandom($results['results'])['id']]);
    }

    public function removeMovie(int $id): RedirectResponse
    {
        $this->removeKeyword('userInput', $id);
        return redirect('/criteria');
    }

    public function removeTv(int $id): RedirectResponse
    {
        $this->removeKeyword('tvInput', $id);
        return redirect('/tv/criteria');
    }

    private function mergeKeyword(string $sessionKey, int $id, string $name): void
    {
        $input = session($sessionKey, []);

        // Replace existing keywords — clicking a keyword from a detail page
        // means "show me more with this vibe", not AND-stacking with prior keywords.
        $input['with_keywords']       = [$id];
        $input['with_keywords_names'] = [$name];

        session([$sessionKey => $input]);
    }

    private function removeKeyword(string $sessionKey, int $id): void
    {
        $input = session($sessionKey, []);
        $ids   = (array) ($input['with_keywords']       ?? []);
        $names = (array) ($input['with_keywords_names'] ?? []);

        $index = array_search($id, array_map('intval', $ids));
        if ($index !== false) {
            array_splice($ids, $index, 1);
            array_splice($names, $index, 1);
        }

        $input['with_keywords']       = $ids ?: null;
        $input['with_keywords_names'] = $names ?: null;

        session([$sessionKey => array_filter($input, fn($v) => $v !== null)]);
    }
}
