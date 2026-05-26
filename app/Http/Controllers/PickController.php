<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

abstract class PickController extends Controller
{
    protected function submitted(Request $request): array
    {
        return array_filter(
            $request->except(['_token', 'i', 'total_pages', 'a']),
            fn($v) => $v !== '' && $v !== null && $v !== []
        );
    }

    protected function handleSessionReset(
        Request $request,
        string $redirectTo,
        string $sessionKey,
        array $defaults,
        array $clearWith = []
    ): ?RedirectResponse {
        if ($request->query('i') === 'new') {
            if ($clearWith) {
                session()->forget($clearWith);
            }
            session([$sessionKey => $defaults]);
            return null;
        }

        if ($request->query('i') !== null && session($sessionKey) !== null) {
            session()->forget(array_merge([$sessionKey], $clearWith));
            return redirect(url($redirectTo));
        }

        if ($request->query('a') !== null) {
            session()->forget($sessionKey);
        }

        return null;
    }

    protected function restoreOrFetch(Request $request, string $mediaType, callable $fetch): array
    {
        if ($request->query('from') === 'roll'
            && session('lastBatchType') === $mediaType
            && session('lastBatchResults')
        ) {
            $results = session('lastBatchResults');
            session()->forget(['lastBatchResults', 'lastBatchType']);
            return ['results' => $results];
        }

        return $fetch();
    }
}
