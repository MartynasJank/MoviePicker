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

### Route Ordering

`/movie/roll` and `/tv/roll` must be declared **before** `/movie/{id}` and `/tv/{id}` in `routes/web.php` to prevent the wildcard from capturing them.

### Frontend

JS lives in `resources/js/custom/`, bundled by Vite. jQuery is injected globally via `@rollup/plugin-inject` — no explicit import needed. All entry points are listed in `vite.config.js`.

- **`caseOpening.js`** — shared module (not a Vite entry point; auto-extracted as a chunk). Exports `runCaseOpening(cards, winnerIdx, url)` and `getTier(rating)`. Handles the animated strip reveal: shuffles neighbours, excludes the winner from surrounding slots, animates with CSS transform, then navigates.
- **`roulettes.js`** — wires up all Roll buttons site-wide via event delegation: `[data-roulette-roll]` fetches `/roulettes/{slug}/movies`, `#batch-roll-btn` scrapes `[data-batch-card]` from the carousel, homepage "Random Movie/TV" links fetch `/movie/roll` or `/tv/roll`. Imports from `caseOpening.js`.
- **`watchlist.js`** — watchlist filter/sort UI, toggle-watched, remove, and the Roll button. Imports from `caseOpening.js`.
- **`criteriaForm.js`** — 5-step form using TomSelect for genre/provider and Flexdatalist autocomplete for actor/director search.
- **`trailerModal.js`** — YouTube trailer modal; also drives the "adjust criteria" step wizard.
- **`carousel.js`** — Swiper instances for similar movies, homepage trending, and batch results.
- **`search.js`** — desktop and mobile search dropdowns.

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

API keys are accessed via `config/api.php`.

## Deployment

GitHub Actions (`.github/workflows/deploy.yml`) triggers on push to `master` (also supports manual `workflow_dispatch`):
1. Installs PHP 8.4 and Node 22
2. Runs `composer install --no-dev` and `npm run build`
3. SCPs `app/`, `bootstrap/`, `config/`, `database/`, `public/`, `resources/views/`, `routes/`, `vendor/` to `/var/www/moviepicker`
4. SSHs in to run `php artisan migrate --force`, `config:cache`, `route:cache`, `view:cache`

Note: `resources/js/` is **not** deployed — only the compiled output in `public/build/` matters. The `.env` file is never deployed and must be created manually on the server.
