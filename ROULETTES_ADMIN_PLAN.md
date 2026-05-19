# Plan: Admin Dashboard + User Custom Roulettes

## Context
The Roulettes V2 feature ships 142 curated system roulettes. This plan adds:
1. **Admin dashboard** — full control over every roulette (CRUD, visibility) and the display order of both rows and roulettes within rows. All ordering is DB-driven; nothing is hardcoded.
2. **User custom roulettes** — logged-in users can create roulettes with a tag builder. Public ones appear in a "Community" row.

Auth is Google OAuth only. Admin is identified by matching `auth()->user()->email` against `ADMIN_EMAIL` in `.env`.

---

## Phase 0 — DB Foundation

### 0.1 Fill in existing sort_order migration
**File:** `database/migrations/2026_05_19_141832_add_sort_order_to_roulettes_table.php`
```php
$table->unsignedSmallInteger('sort_order')->default(0)->after('is_public');
```
Add `'sort_order'` to `Roulette::$fillable`.

### 0.2 Settings table
**New migration:** `create_settings_table`
```
key   varchar(100) primary key
value json
```
**New model:** `app/Models/Setting.php`
```php
public static function get(string $key, mixed $default = null): mixed
public static function set(string $key, mixed $value): void
```
Used to store row display order as `roulette_row_order` → JSON array of row names.

### 0.3 Seeder updates
- `RouletteSeeder` — set `sort_order` for every entry (position within its group, 0-based)
- New `SettingSeeder` — seeds `roulette_row_order` with default: `['By Decade', 'Netflix', 'Prime Video', 'HBO', 'Disney+', 'Apple TV+', 'World Cinema', 'Anime', 'Community', 'By Genre']`
- Add `SettingSeeder` to `DatabaseSeeder`

### 0.4 Controller update (interim)
Replace hardcoded `$sortKeys` arrays in `RouletteController::index()` with:
- Row order: `Setting::get('roulette_row_order', $defaultOrder)`
- Within-row order: groups already sorted by `->orderBy('sort_order')` on the DB query

---

## Phase 1 — Admin Dashboard

### 1.1 Admin Middleware
**New file:** `app/Http/Middleware/AdminMiddleware.php`
```php
if (!auth()->check() || auth()->user()->email !== env('ADMIN_EMAIL')) {
    abort(403);
}
```
- Add `ADMIN_EMAIL=` to `.env` and `.env.example`
- Register alias `'admin'` in `app/Http/Kernel.php` → `$routeMiddleware`

### 1.2 Routes
**Modify:** `routes/web.php` — add before the `{slug}` wildcard route
```php
Route::prefix('admin')->middleware(['auth', 'admin'])->name('admin.')->group(function () {
    Route::resource('roulettes', Admin\AdminRouletteController::class);
    Route::patch('roulettes/{roulette}/toggle',  [Admin\AdminRouletteController::class, 'togglePublic'])->name('roulettes.toggle');
    Route::patch('roulettes/reorder',            [Admin\AdminRouletteController::class, 'reorder'])->name('roulettes.reorder');
    Route::get('rows',                           [Admin\RowOrderController::class, 'index'])->name('rows.index');
    Route::patch('rows/reorder',                 [Admin\RowOrderController::class, 'reorder'])->name('rows.reorder');
});
```

### 1.3 AdminRouletteController
**New file:** `app/Http/Controllers/Admin/AdminRouletteController.php`

- `index()` — paginated, searchable by name (`?q=`), filterable by row/group
- `create()` / `store()` — form + save; auto-assign `sort_order` = max in group + 1
- `edit(Roulette)` / `update(Roulette)` — recalculate `tag_fingerprint` on save
- `destroy(Roulette)` — soft-guard: confirm before deleting `is_system` roulettes
- `togglePublic(Roulette)` — flip `is_public`, redirect back
- `reorder()` — PATCH, receives `[{id, sort_order}]` array, bulk-updates in one transaction

Reuse: `Roulette::fingerprintFromTags()`, `Roulette::generateSlug()`

### 1.4 RowOrderController
**New file:** `app/Http/Controllers/Admin/RowOrderController.php`

- `index()` — show draggable list of all known row names (from settings + any in DB)
- `reorder()` — PATCH, receives ordered array of row names, saves to `Setting::set('roulette_row_order', ...)`

### 1.5 Admin Views
All extend `layouts.app`.

**`resources/views/admin/roulettes/index.blade.php`**
- Table: name, row/group, sort_order, is_system badge, is_public toggle, edit/delete
- Search input filters by name
- Rows are visually grouped; within each group items are draggable via SortableJS
- On drag-end: AJAX PATCH `/admin/roulettes/reorder` with new `[{id, sort_order}]`
- "New Roulette" button → `/admin/roulettes/create`

**`resources/views/admin/roulettes/form.blade.php`** (shared create/edit)
- Fields: Name, Slug (auto-generated from name, editable), Description
- Tag builder (same `_tag_form` partial as user form)
- is_public checkbox

**`resources/views/admin/rows/index.blade.php`**
- Draggable list of row names with SortableJS
- On drag-end: AJAX PATCH `/admin/rows/reorder` with new ordered array
- Visual: each row shows as a draggable chip/card with its name

SortableJS loaded from CDN (`https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js`) on admin pages only — no Vite changes needed.

CSS conventions: `input-dark`, `btn-accent`, `btn-secondary`, dark Tailwind classes.

---

## Phase 2 — User Custom Roulettes

### 2.1 Slug auto-generation in Model
**Modify:** `app/Models/Roulette.php`
```php
public static function generateSlug(string $name, ?int $excludeId = null): string
{
    $base = Str::slug($name);
    $slug = $base;
    $i = 1;
    while (static::where('slug', $slug)->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))->exists()) {
        $slug = $base . '-' . $i++;
    }
    return $slug;
}
```

### 2.2 Routes
**Modify:** `routes/web.php`
```php
Route::middleware('auth')->group(function () {
    Route::resource('my-roulettes', UserRouletteController::class)->except(['show']);
    Route::post('roulettes/preview-poster', [UserRouletteController::class, 'previewPoster'])->name('roulettes.preview-poster');
});
```
Note: `POST roulettes/preview-poster` must be defined **before** `GET roulettes/{slug}`.

### 2.3 UserRouletteController
**New file:** `app/Http/Controllers/UserRouletteController.php`

- `index()` — user's roulettes, `Roulette::where('user_id', auth()->id())->orderBy('created_at','desc')->get()`
- `create()` — blank tag builder form
- `store()` — validate → build tags → fingerprint → unique check (`unique:roulettes,tag_fingerprint`) → generate slug → save with `user_id`, `is_system=false`. Limit: 20 per user.
- `edit(Roulette)` — `abort_if($roulette->user_id !== auth()->id(), 403)`
- `update(Roulette)` — same ownership check; fingerprint uniqueness: `unique:roulettes,tag_fingerprint,{id}`
- `destroy(Roulette)` — same ownership check
- `previewPoster(TmdbClient $tmdb)` — parse tags from request, call `RouletteTagMapper::toCriteria()`, strip platform (region-specific), call `$tmdb->discover($criteria, 'US')`, return `{poster_path: '/...' }` as JSON. Returns `{poster_path: null}` on failure.

### 2.4 Tag Builder Partial
**New file:** `resources/views/roulettes/_tag_form.blade.php`

Sections (all optional, at least one required):
- **Genre** — checkbox grid, all 17 genres
- **Platform** — `<select>`: None / Netflix / Prime Video / HBO / Disney+ / Apple TV+
- **Era** — `<select>`: None / New Releases / 2020s / 2010s / … / Classic Hollywood
- **Language** — `<select>`: None + 11 supported languages

**Poster preview panel:**
- "Preview Poster" button → JS calls `previewPoster` endpoint with current tag selection
- Shows returned poster in a small 2:3 card, or a placeholder if none

Validation display: Blade `@error` directives for each field.

### 2.5 User Roulette Views
**`resources/views/my-roulettes/index.blade.php`**
- Cards or table listing user's roulettes: name, tags, public badge, roll/edit/delete links
- "Create New Roulette" button

**`resources/views/my-roulettes/create.blade.php`** — wraps `_tag_form` partial

**`resources/views/my-roulettes/edit.blade.php`** — wraps `_tag_form` partial, pre-fills from `$roulette`

### 2.6 Roulettes Index — Community + My Roulettes
**Modify:** `app/Http/Controllers/RouletteController.php` `index()`

```php
// System roulettes only for the main grouped display
$roulettes = Roulette::where('is_public', true)->where('is_system', true)
    ->orderBy('sort_order')->orderBy('id')->get();

// Community: public user-created roulettes
$communityRoulettes = Roulette::where('is_system', false)->where('is_public', true)
    ->orderBy('created_at', 'desc')->get();

// My Roulettes: current user's own (auth-gated)
$myRoulettes = auth()->check()
    ? Roulette::where('user_id', auth()->id())->orderBy('created_at', 'desc')->get()
    : collect();
```

Pass `$communityRoulettes` and `$myRoulettes` to view alongside `$grouped`.
Row order comes from `Setting::get('roulette_row_order', $defaultOrder)`.

**Modify:** `resources/views/roulettes.blade.php`
- Add "My Roulettes" horizontal row at top (only if `$myRoulettes->isNotEmpty()` and auth)
- Community row rendered from `$communityRoulettes` (separate variable, not in `$grouped`)
- Add "Create your own →" link in page header (auth-gated, points to `/my-roulettes/create`)

---

## Files Summary

**New files:**
- `database/migrations/*_create_settings_table.php`
- `database/seeders/SettingSeeder.php`
- `app/Models/Setting.php`
- `app/Http/Middleware/AdminMiddleware.php`
- `app/Http/Controllers/Admin/AdminRouletteController.php`
- `app/Http/Controllers/Admin/RowOrderController.php`
- `app/Http/Controllers/UserRouletteController.php`
- `resources/views/admin/roulettes/index.blade.php`
- `resources/views/admin/roulettes/form.blade.php`
- `resources/views/admin/rows/index.blade.php`
- `resources/views/my-roulettes/index.blade.php`
- `resources/views/my-roulettes/create.blade.php`
- `resources/views/my-roulettes/edit.blade.php`
- `resources/views/roulettes/_tag_form.blade.php`

**Modified files:**
- `database/migrations/2026_05_19_141832_add_sort_order_to_roulettes_table.php` — fill in the column
- `database/seeders/RouletteSeeder.php` — add sort_order per entry
- `database/seeders/DatabaseSeeder.php` — add SettingSeeder
- `app/Models/Roulette.php` — add generateSlug(), sort_order to fillable
- `app/Http/Kernel.php` — register 'admin' middleware alias
- `app/Http/Controllers/RouletteController.php` — DB-driven order, community + my roulettes
- `resources/views/roulettes.blade.php` — My Roulettes + Community rows + Create CTA
- `routes/web.php` — admin + user roulette routes
- `.env` / `.env.example` — add ADMIN_EMAIL=

---

## Verification

1. Run `php artisan migrate && php artisan db:seed`
2. `/admin/roulettes` — full roulette table; drag items within a group to reorder → sort_order updates via AJAX
3. `/admin/rows` — drag rows to reorder → row order persists across page reloads
4. Create a roulette via admin form → appears in correct group on `/roulettes`
5. Toggle is_public off → roulette disappears from `/roulettes`
6. Log in as regular user → create a custom roulette via `/my-roulettes/create` → appears in "My Roulettes" row on `/roulettes`
7. Make user roulette public → appears in "Community" row for all visitors
8. Non-admin hits `/admin/roulettes` → 403
9. User tries to edit another user's roulette → 403