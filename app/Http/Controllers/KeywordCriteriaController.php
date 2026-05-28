<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class KeywordCriteriaController extends Controller
{
    public function movie(int $id, string $name): RedirectResponse
    {
        $this->mergeKeyword('userInput', $id, $name);
        return redirect('/criteria');
    }

    public function tv(int $id, string $name): RedirectResponse
    {
        $this->mergeKeyword('tvInput', $id, $name);
        return redirect('/tv/criteria');
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

        $ids   = (array) ($input['with_keywords']       ?? []);
        $names = (array) ($input['with_keywords_names'] ?? []);

        if (!in_array($id, $ids)) {
            $ids[]   = $id;
            $names[] = $name;
        }

        $input['with_keywords']       = $ids;
        $input['with_keywords_names'] = $names;

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
