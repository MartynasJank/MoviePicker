# Refactoring Plan

Systematic cleanup to remove duplication and make the codebase easier to read and maintain.
Each phase is a standalone PR on the `refactor` branch.

## Phase 1 — MovieService paired methods ✅
Collapsed 6 methods into 3 by adding a `$mediaType` parameter:
- `resolvePage` / `resolveTvPage` → `resolvePage(..., $mediaType = 'movie')`
- `resolveRoulettePage` / `resolveRoulettePageTv` → `resolveRoulettePage(..., $mediaType = 'movie')`
- `genres` / `tvGenres` → `genres(..., $mediaType = 'movie')`

## Phase 2 — Base Controller helpers ✅
Added two protected methods to `Controller.php` to eliminate copy-pasted patterns:
- `savedWatchlistIds()` — 3-line auth+pluck that appeared in 6 controllers
- `toRollCards(array $items, string $mediaType)` — 4-field JSON card shape built 10 times across 4 controllers

## Phase 3 — Pick controller base class
`MoviePickController` and `TvPickController` are ~80% identical. Extract a `PickController` base with:
- `submitted(Request $request)` — identical private method in both (strips empty/null/token fields)
- `handleSessionReset()` — same logic, different session keys and default values
- Batch restore logic — identical `?from=roll` session restore block in both `batch()` methods

Both pick controllers become thin subclasses.

Also: move `TvPickController::resolveSessionCriteria` into `MovieService` with a `$sessionKey`
parameter, so both controllers call one shared method.

## Phase 4 — TV normalisation
`normaliseShows()` (`name → title`, `first_air_date → release_date`) exists as a private method
in `TvShowController` but is copy-pasted inline in `TvPickController::batch` and
`RouletteController::pick`. Move it to `MovieService::normaliseShows()`.

## Phase 5 — View deduplication
- `$platformLogos` and `$tagLabels` PHP arrays are copy-pasted in `roulettes.blade.php` and
  `my-roulettes/index.blade.php` → extract to a shared `@include` or view composer.
- Audit `batch.blade.php` vs `tv/batch.blade.php` for shared markup.

## Phase 6 — JS cleanup
- `roulettes.js` (217 lines) handles too many concerns: roulette rolls, homepage rolls, criteria
  form submit, person rolls, batch link tracking. Split into logical sections or lighter modules.
- Check `watchlist.js` for patterns shared with `roulettes.js` (both use `caseOpening.js`).
