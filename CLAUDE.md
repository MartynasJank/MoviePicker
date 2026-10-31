# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

MoviePickr is a Laravel 12 PHP application that picks random movies and TV shows based on user criteria (genre, year range, streaming provider, rating). It integrates with TMDB, OMDb, and YouTube APIs. The deployed app is at https://moviepicker.martybuilds.dev/.

## Commands

**Frontend:**
```bash
npm run dev          # development build with HMR
npm run build        # production build (also aliased as npm run prod)
```

**Backend:**
```bash
php artisan migrate        # run migrations
php artisan db:seed        # seed database
php artisan tinker         # interactive REPL
```

**Testing:**
```bash
./vendor/bin/phpunit                          # all tests
./vendor/bin/phpunit --filter TestName        # single test
./vendor/bin/phpunit tests/Unit               # unit tests only
./vendor/bin/phpunit tests/Feature            # feature tests only
```

Tests use in-memory SQLite (configured in `phpunit.xml`), so no database setup is needed for testing.

## Architecture

### Request Flow

1. `public/index.php` bootstraps `bootstrap/app.php`
2. Routes in `routes/web.php` dispatch to controllers
3. Controllers delegate to `app/Services/MovieService.php` for business logic
4. `MovieService` uses `app/Services/TmdbClient.php` (primary) or `app/Services/OmdbClient.php` (metadata enrichment)
5. Blade templates in `resources/views/` render the response

### Key Services

- **`app/Services/TmdbClient.php`** — TMDB API client (discover, trending, genres, watch providers, trailers, people, TV seasons/episodes). Bearer token auth + GuzzleHTTP. Implements `MovieApiInterface`.
- **`app/Services/OmdbClient.php`** — OMDb API client (Rotten Tomatoes scores, detailed plot) via cURL. Implements `MovieApiInterface`.
- **`app/Services/MovieService.php`** — Core business logic: random pick/batch, genre caching, page resolution, streaming provider links, geolocation via `stevebauman/location`.
- **`app/Services/RatingsUrlBuilder.php`** — Builds IMDb, Rotten Tomatoes (with slug fallbacks), and Metacritic URLs.

### Controllers

**Movies:**
- `MoviePickController` — `/movie` (single pick) and `/multiple` (batch); `rollJson()` returns JSON for animation
- `MovieController` — `/movie/{id}` detail page: ratings, cast, crew, providers, trailers, similar

**TV Shows:**
- `TvPickController` — `/tv/pick` and `/tv/multiple`; `rollJson()` for animation
- `TvShowController` — `/tv/{id}` show detail
- `TvSeasonController` / `TvEpisodeController` — season and episode detail pages
- `TvCriteriaController` — TV criteria form

**People:**
- `PersonController` — `/person/{id}` filmography page
- `PersonRollController` — rolls a random movie/TV show from a person's credits

**Roulettes:**
- `RouletteController` — system curated collections; `movies()` returns JSON list for roll animation
- `UserRouletteController` — authenticated CRUD for personal roulettes (`/my-roulettes`)

**Watchlist:**
- `WatchlistController` — save/remove/status toggle; `roll()` returns JSON for animation

**Other:**
- `HomeController` — homepage trending
- `CriteriaController` — movie criteria form
- `TmdbProxyController` — proxies TMDB people/movie/TV search to keep API key off browser
- `UserInputController` — `GET /userinput` returns session form values as JSON
- `Admin/` — admin dashboard, roulette management, row ordering, user management
- `CollabBatchController` — `/batch/collab` create (auth-only), show, join/leave/heartbeat, vote (veto/restore), toggleReady, toggleRefreshVote, removeVotes. Broadcasts `CollabStateUpdated` event on every state change via Laravel Reverb.

### Route Ordering

`/movie/roll` and `/tv/roll` must be declared **before** `/movie/{id}` and `/tv/{id}` in `routes/web.php` to prevent the wildcard from capturing them.

### Frontend

JS lives in `resources/js/custom/`, bundled by Vite. jQuery is injected globally via `@rollup/plugin-inject` — no explicit import needed. All entry points are listed in `vite.config.js`.

- **`caseOpening.js`** — shared module (not a Vite entry point; auto-extracted as a chunk). Exports `runCaseOpening(cards, winnerIdx, url, mediaType, onComplete?)` and `getTier(rating)`. Handles the animated strip reveal: shuffles neighbours, excludes the winner from surrounding slots, animates with CSS transform, then navigates (or calls `onComplete` if provided — used by collab batch to show the winner overlay instead of navigating).
- **`roulettes.js`** — wires up all Roll buttons site-wide via event delegation: `[data-roulette-roll]` fetches `/roulettes/{slug}/movies`, `#batch-roll-btn` scrapes `[data-batch-card]` from the carousel, homepage "Random Movie/TV" links fetch `/movie/roll` or `/tv/roll`. Imports from `caseOpening.js`.
- **`watchlist.js`** — watchlist filter/sort UI, toggle-watched, remove, and the Roll button. Imports from `caseOpening.js`.
- **`criteriaForm.js`** — 5-step form using TomSelect for genre/provider and Flexdatalist autocomplete for actor/director search.
- **`trailerModal.js`** — YouTube trailer modal; also drives the "adjust criteria" step wizard.
- **`carousel.js`** — Swiper instances for similar movies, homepage trending, and batch results.
- **`search.js`** — desktop and mobile search dropdowns.
- **`collabBatch.js`** — real-time collaborative batch voting. Connects via Laravel Echo to `batch.{token}` channel. Manages participant identity in localStorage, renders vote heat overlays and pip dots, drives the graveyard and rules cards, triggers `runCaseOpening` with a callback when roll threshold is met.

Batch carousel cards include `data-batch-card`, `data-title`, `data-rating`, `data-poster`, and `data-url` attributes so `roulettes.js` can scrape them without an extra API call.

### Roll Animation Overlay

`resources/views/includes/case-overlay.blade.php` is included in the base layout (`layouts/app.blade.php`) so it's available on every page. The strip element uses `top-3` (12px) to give enough clearance for the winner card's scale transform.

### CSS / Theming

Bootstrap 4 handles layout; Tailwind CSS 4 is also included. Dark/light theme switching uses CSS custom properties stored in a `theme` cookie, toggled via `.theme-toggle` buttons.

### Database

- **`users`** — Google OAuth accounts (`name`, `email`, `avatar`, `google_id`)
- **`clicks`** — Analytics: `visitor` (cookie hash), `input` (JSON criteria), `result` (TMDB movie ID)
- **`roulettes`** — both system and user-created collections; `tags` (JSON), `poster_paths` (JSON), `row`, `sort_order`, `is_public`, `media_type`
- **`watchlist`** — `user_id`, `tmdb_id`, `type` (movie/tv), `status` (saved/watched), poster/title/year/genres/vote_average
- **`settings`** — key/value store for admin-controlled site settings
- **`collab_batches`** — collaborative sessions: `token` (8-char PK), `movies`/`graveyard`/`votes`/`restore_votes`/`ready`/`refresh_votes`/`participants`/`criteria` (JSON), `media_type`, `expires_at` (24h TTL)

### Auth

Google OAuth via Laravel Socialite. Admin access is gated by matching `config('api.admin_email')`. Dev environment exposes `/dev/login` for local testing.

## Environment Setup

Copy `.env.example` to `.env` and fill in:
- `TMDB_API_KEY` — TMDB API v3 key
- `OMDB_API_KEY` — OMDb API key
- `YOUTUBE_API_KEY` — YouTube Data API key
- `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` / `GOOGLE_REDIRECT_URI`
- `ADMIN_EMAIL` — Google account email that gets admin access
- MySQL credentials
- `REVERB_APP_ID` / `REVERB_APP_KEY` / `REVERB_APP_SECRET` — any random strings (Reverb is self-hosted, these are just used to authenticate the WebSocket connection)

API keys are accessed via `config/api.php`.

**Running Reverb locally:**
```bash
php artisan reverb:start
```
Reverb must be running for the collaborative batch feature to work. In production it runs under supervisor (see Deployment below).

## Deployment

GitHub Actions (`.github/workflows/deploy.yml`) triggers on push to `master` (also supports manual `workflow_dispatch`):
1. Installs PHP 8.4 and Node 22
2. Runs `composer install --no-dev` and `npm run build`
3. SCPs `app/`, `bootstrap/`, `config/`, `database/`, `public/`, `resources/views/`, `routes/`, `vendor/` to `/var/www/moviepicker`
4. SSHs in to run `php artisan migrate --force`, `config:cache`, `route:cache`, `view:cache`

Note: `resources/js/` is **not** deployed — only the compiled output in `public/build/` matters. The `.env` file is never deployed and must be created manually on the server.

**Reverb in production** — must run as a persistent process under supervisor:
```ini
# /etc/supervisor/conf.d/reverb.conf
[program:reverb]
command=php /var/www/moviepicker/artisan reverb:start
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/reverb.log
```
```bash
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start reverb
```
The deploy script runs `php artisan reverb:restart` automatically on each deploy so Reverb reloads new config without manual intervention.

===

<laravel-boost-guidelines>
=== .ai/moviepicker rules ===

# MoviePickr project conventions

- This is an existing Laravel 12 application using the older Kernel/provider layout. Follow the existing structure; do not migrate it to a new skeleton as part of feature work.
- PHP 8.4 is the deployment target; Composer pins that platform. Keep changes compatible with PHP 8.4 even when local Herd uses PHP 8.5.
- Read nearby code and tests before editing. Keep changes focused on the requested behavior.
- MovieService owns shared picking logic. Use TmdbClient and OmdbClient for external metadata; use config/api.php for credentials, never expose API keys in browser code.
- Keep movie/TV roll routes before wildcard ID routes. Keep movie and TV session criteria separate (userInput and tvInput).
- InternalOnly middleware requires X-Requested-With: XMLHttpRequest. JSON Accept headers alone do not satisfy it; preserve this behavior in tests.
- Frontend uses Blade, jQuery, Bootstrap 4 and Tailwind 4 with Vite. Reuse resources/js/custom/caseOpening.js for roll animations. Check vite.config.js when changing entry points.
- Preserve watchlist and user-roulette ownership checks, admin authorization, and Google OAuth behavior. The local /dev/login route must remain local-only.
- Collaborative batches use Laravel Reverb. Preserve event payload compatibility between PHP events and resources/js/custom/collabBatch.js.
- Run composer test for backend changes and npm run build for frontend changes. Add focused regression tests for behavior changes. Mock external clients; tests must not require real API keys, OAuth, MySQL, or Reverb.
- Tests use SQLite :memory: and array sessions/cache. Use RefreshDatabase only for tests that need database tables. Production migrations must remain MySQL-compatible; do not rewrite applied migrations.
- Never use migrate:fresh, reset the local database, or seed over user data as a routine verification step. Never commit .env or credentials.
- Read README.md for local setup and docs/collaborative-batch.md for collaborative voting details. Historical PLAN.md and REFACTOR.md are context, not standing requirements.

=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- This project upgraded from Laravel 10 without migrating to the new streamlined Laravel file structure.
- This is perfectly fine and recommended by Laravel. Follow the existing structure from Laravel 10. We do not need to migrate to the new Laravel structure unless the user explicitly requests it.

## Laravel 10 Structure

- Middleware typically lives in `app/Http/Middleware/` and service providers in `app/Providers/`.
- There is no `bootstrap/app.php` application configuration in a Laravel 10 structure:
    - Middleware registration happens in `app/Http/Kernel.php`
    - Exception handling is in `app/Exceptions/Handler.php`
    - Console commands and schedule register in `app/Console/Kernel.php`
    - Rate limits likely exist in `RouteServiceProvider` or `app/Http/Kernel.php`

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.

- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
