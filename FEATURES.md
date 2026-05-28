# Feature Roadmap

Planned improvements based on a TMDB API audit. Each section covers the idea, the decision rationale, and a concrete implementation plan.

---

## 1. Recommendations

### Decision
Keep **both** recommendations and "More with Same Criteria" — they serve different intents and neither replaces the other.

- **Recommendations** (`/movie/{id}/recommendations`, `/tv/{id}/recommendations`) — algorithmic, "people who liked this also liked X". Shown when the user rolled from anywhere **except** the criteria form.
- **More with Same Criteria** — keeps the user inside their chosen filters. Shown only when the user actively configured and rolled from the criteria page.
- **Similar** (current fallback) — content-matching by genre tags. Only shown if recommendations returns empty.

### Tracking roll origin
The session key `userInput` is not a reliable signal — it's always populated (even homepage rolls write the defaults into it). Instead, set an explicit **`roll_source`** flag at the moment of rolling:

- `MoviePickController::single()` → `session(['roll_source' => 'criteria'])`
- `TvPickController::single()` → `session(['roll_source' => 'criteria'])`
- All other roll paths (homepage, roulette, person, watchlist) → `session(['roll_source' => 'other'])`

The detail page then checks `session('roll_source') === 'criteria'` to decide which section to display. This captures **intent at the moment of rolling**, not whatever happens to be in session from a previous flow.

### Logic on detail pages
```
if roll_source === 'criteria'  → "More with Same Criteria" carousel
else                           → "You Might Also Like" (recommendations)
                                 fallback to Similar if recommendations empty
```

### Implementation
- Add `recommendations` to `append_to_response` in `TmdbClient::movie()` and `TmdbClient::tvShow()`
- Set `roll_source` in `MoviePickController::single()` and `TvPickController::single()`
- Clear / set `roll_source = 'other'` in `homepageRoll()`, `RouletteController::moviesJson()`, `PersonRollController`, `WatchlistController::roll()`
- In `MovieController` and `TvShowController`: branch on `session('roll_source')`
- Rename the carousel heading dynamically: "More with Same Criteria" / "You Might Also Like" / "Similar"
- No extra API calls — recommendations is just one more key in the existing append

---

## 2. Keywords

Two distinct features that build on each other.

### 2a. Keywords on detail pages (context-aware drill-down)

**What**: Show a movie/TV show's keyword tags as clickable chips on the detail page (below genres). Clicking a keyword **merges it into the user's current criteria** and redirects to the criteria page pre-populated with the combined filters — so the user can review what's active and roll from there.

**Examples**: User was rolling "Action, 1990s, Netflix" and clicks "heist" on a detail page → criteria page shows Action + 1990s + Netflix + keyword "heist". They roll and get heist action films from the 90s on Netflix.

**If no criteria session**: keyword is added to an otherwise empty criteria state — user lands on the criteria page with just that keyword pre-filled, effectively a genre-free keyword-only discover.

**Flow**
1. User clicks keyword chip on detail page
2. Server merges `with_keywords: [id]` into `session('userInput')` (or `tvInput` for TV)
3. Redirect to `/criteria` (or `/tv/criteria`) — form auto-populates from session including the new keyword
4. User sees the full combined criteria and can adjust before rolling, or just hit Roll

**How keywords are fetched**

Add `keywords` to `append_to_response` in `TmdbClient::movie()` and `TmdbClient::tvShow()` — zero extra API calls, bundled with the existing detail request. TMDB returns all keywords for a title (anywhere from 3 to 20+).

- Movie response: `$tmdbInfo->keywords->keywords` → array of `{id, name}`
- TV response: `$tmdbInfo->keywords->results` → array of `{id, name}` (different key — normalise in controller)

Display the **first 3–5** as chips. TMDB orders them by the sequence they were added, so the first few tend to be the most relevant. No need to rank or filter further.

**Implementation**
- Add `keywords` to `append_to_response` in `TmdbClient::movie()` and `TmdbClient::tvShow()`
- In `MovieController` and `TvShowController`: normalise keyword array, slice to 5, pass to view
- Render chips on detail pages beneath the genre line
- Each chip links to `GET /criteria/keyword/{id}/{name}` or `GET /tv/criteria/keyword/{id}/{name}`
- New `KeywordCriteriaController` — merges `with_keywords` into session, stores keyword name alongside for display, redirects to criteria page
- Criteria form displays selected keywords as dismissible pills (same pattern as actors/crew)
- `CriteriaController` and `TvCriteriaController` pass keyword names from session so the form can pre-populate with human-readable labels not just IDs

### 2b. Keyword filter in criteria form (sub-genre selector)

**What**: An autocomplete search field in the criteria form (like the current actors/crew search) that lets users pick keywords as "sub-genres". Results stored as `with_keywords[]` and `without_keywords[]` in session.

**Examples**: User picks genre "Thriller" + keyword "heist" → very targeted discover

**Implementation**
- Add `/tmdb/search/keywords` proxy endpoint in `TmdbProxyController` → calls `/search/keyword?query=`
- Add keyword include/exclude fields to criteria form and criteria modal (TomSelect, same pattern as actors)
- Store keyword IDs in session criteria, pass through to `TmdbClient::discover()`
- `discover()` already accepts arbitrary params so no changes needed there

### 2c. Keyword-based roulettes (admin + personal)

**What**: Both admin and logged-in users can add keywords to a roulette definition (e.g., "Heist Movies", "Time Loop Films") alongside the existing genre/platform/decade tags. Keywords narrow discover results more precisely than genre alone.

**Scope**: Works identically in both `AdminRouletteController` (system roulettes) and `UserRouletteController` (personal roulettes) — same form fields, same tag storage, same `RouletteTagMapper` mapping.

**Implementation**
- Add a keyword autocomplete field to both the admin roulette form and the personal roulette create/edit form — same pattern as the `/tmdb/search/keywords` proxy added for the criteria form (section 2b)
- Extend `RouletteTagMapper` to recognise `keyword:{id}` tags and map them to `with_keywords` in discover criteria
- Store selected keywords as `keyword:10181` entries in the `tags` JSON column (consistent with existing `genre:28`, `platform:netflix` pattern)
- Display selected keyword pills in the tag editor the same way genre/decade pills are shown today

---

## 3. Reviews — ~~Scrapped~~

Evaluated and dropped. TMDB's `append_to_response=reviews` only returns page 1 with no sort control, so we can't guarantee surfacing a representative review. A 3/10 outlier showing as the default review on a 9.5-rated show misrepresents it. Detail pages are already the heaviest pages and fetching additional review pages would make it worse.
- In `MovieController` and `TvShowController`: pass `$tmdbInfo->reviews->results` (sliced to 2) to view
- New `includes/reviews.blade.php` partial — include it on both detail pages
- Style as simple dark cards with author initial avatar, rating badge, truncated text

---

## 4. Backdrops

**What**: `backdrop_path` already comes back on every TMDB movie/TV detail response but is completely unused. Use it as a blurred, dimmed hero background behind the title row on movie and TV detail pages — gives each page a cinematic feel with zero extra API calls.

**Implementation**
- Detail pages: position a full-width `<div>` with `backdrop_path` as background image behind the title/poster grid. CSS: `bg-cover`, low opacity (~15–20%), blurred. Falls back gracefully to plain dark background if no backdrop exists.
- `backdrop_path` is already in the detail response — no API changes needed.

---

## 5. Multi-search

**What**: Replace the current 3 separate search proxy calls (movies, TV, people) with a single `/search/multi` call that returns all three types in one response, each tagged with `media_type`.

**Why**: Simpler code, one fewer round trip, unified result ordering by TMDB relevance across all types.

**Current**: `TmdbProxyController` has `searchMovies()`, `searchTv()`, `searchPeople()` called separately by `search.js`

**Implementation**
- Add `TmdbProxyController::searchAll()` → calls `/3/search/multi?query=`
- Add route `GET /tmdb/search/all`
- Update `search.js` to call the single endpoint; use `result.media_type` to route display (movie/tv/person already have different fields — `title` vs `name`, `release_date` vs `first_air_date`)
- Add small type badge to search results ("Movie", "TV", "Person") so users can distinguish
- Keep the old 3 endpoints in place temporarily in case anything else uses them, remove once confirmed clean

---

## Implementation Order (suggested)

| # | Feature | Effort | Impact |
|---|---------|--------|--------|
| 1 | Multi-search | Small | Medium |
| 2 | Recommendations on detail pages | Small | High |
| 3 | Backdrops on detail pages | Small | Medium |
| 4 | Reviews on detail pages | Small | Medium |
| 5 | Keywords on detail pages | Medium | High |
| 6 | Keyword criteria filter | Large | High |
| 7 | Keyword-based roulettes | Large | High |
