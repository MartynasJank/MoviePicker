# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

MoviePickr is a Laravel 12 PHP application that recommends random movies based on user criteria (genre, year range, streaming provider, rating). It integrates with TMDB, OMDb, and YouTube APIs. The deployed app is at https://moviepicker.martybuilds.dev/.

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
4. `MovieService` calls `app/TMDB.php` (primary) or `app/OMDB.php` (metadata enrichment)
5. Blade templates in `resources/views/` render the response

### Key Files

- **`app/TMDB.php`** — TMDB API client (discover, trending, genres, watch providers, trailers). Uses Bearer token auth + GuzzleHTTP. Implements `ApiMovieInterface`.
- **`app/OMDB.php`** — OMDb API client (Rotten Tomatoes scores, detailed plot) via cURL. Implements `ApiMovieInterface`.
- **`app/Interfaces/ApiMovieInterface.php`** — Interface enforcing `movie($id)` on both API clients.
- **`app/Services/MovieService.php`** — Core business logic: random movie selection, genre caching, trailer region filtering, streaming provider links.
- **`app/Services/UrlGenerator.php`** — Builds URLs for IMDb, Rotten Tomatoes (with slug fallbacks), and Metacritic.
- **`app/Http/Controllers/RandomMovieController.php`** — Handles single/multiple random movie requests; saves `Click` records.
- **`app/Http/Controllers/RoulettesController.php`** — Pre-built curated collections (Netflix Horror, Documentaries, Anime).
- **`app/Http/Controllers/MovieController.php`** — Detailed movie view: ratings, cast, crew, watch providers, similar movies, trailers, reviews.
- **`app/Http/Controllers/HomeController.php`** — Homepage with trending movies from TMDB.
- **`app/Http/Controllers/CriteriaController.php`** — Renders search form with genres and streaming providers.
- **`app/Http/Controllers/AjaxController.php`** — `GET /userinput` returns session-stored form values as JSON (used by JS to repopulate the form on page load).
- **`app/Http/Requests/CheckFormData.php`** — Validates search input: year range bounds (1874–current), year_from ≤ year_to, vote average 0–10.
- **`app/Models/Click.php`** — Records each movie discovery: visitor hash (cookie), JSON input criteria, resulting movie ID. Used for analytics.
- **`routes/web.php`** — All web routes; also defines AJAX routes and obfuscated utility routes for cache/schedule clearing.

### Frontend

Most UI logic lives in `resources/js/custom/` as vanilla JS/jQuery:
- **`customForm.js`** — SmartWizard 5-step form, bootstrap-select dropdowns, Flexdatalist autocomplete for actors/crew/movies (calls TMDB API directly from the browser).
- **`customModal.js`** — YouTube trailer modal with region restriction checking; prevents scrollbar jump on modal open.
- **`customOwlCarousel.js`** — Three OWL Carousel instances: similar movies, homepage trending, multiple results.
- **`showMore.js`** — Expands truncated lists (cast, crew, production companies) with "Show All" toggle.

Bootstrap 4 handles layout. Dark/light theme switching uses CSS variables in `resources/sass/themes/`. jQuery is injected globally via `@rollup/plugin-inject` in `vite.config.js`.

### Geolocation

`stevebauman/location` (IP geolocation) determines the user's country in `MovieService::getUserCountry()` to filter TMDB watch providers so only locally available streaming options are shown.

### Database

Two tables:
- **`users`** — Standard Laravel auth table (not actively used for authentication in current app).
- **`clicks`** — Analytics: `visitor` (cookie hash), `input` (JSON search criteria), `result` (TMDB movie ID).

## Environment Setup

Copy `.env.example` to `.env` and fill in:
- `TMDB_API_KEY` — TMDB API v3 key
- `OMDB_API_KEY` — OMDb API key
- `YOUTUBE_API_KEY` — YouTube Data API key
- MySQL credentials

For production also set:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://moviepicker.martybuilds.dev`

API keys are accessed via `config/api.php`.

## Deployment

GitHub Actions (`.github/workflows/deploy.yml`) triggers on push to `master`:
1. Installs PHP 8.4 and Node 22
2. Runs `composer install --no-dev` and `npm run build`
3. SCPs app files to `/var/www/moviepicker` on the VPS
4. SSHs in to run `php artisan migrate --force`, `config:cache`, `route:cache`, `view:cache`

Required GitHub secrets: `SSH_HOST`, `SSH_USER`, `SSH_KEY`.

The `.env` file is never deployed — it must be created manually on the server once.
