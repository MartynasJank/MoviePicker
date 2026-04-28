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

- **`app/TMDB.php`** — TMDB API client (discovery, trending, genres, watch providers, trailers). Implements `ApiMovieInterface`.
- **`app/OMDB.php`** — OMDb API client (Rotten Tomatoes scores, detailed plot). Implements `ApiMovieInterface`.
- **`app/Services/MovieService.php`** — Core business logic: random movie selection, genre caching, trailer region filtering, streaming provider links.
- **`app/Services/UrlGenerator.php`** — Builds external URLs (review sites, streaming platforms).
- **`app/Http/Controllers/RandomMovieController.php`** — Handles single/multiple random movie requests.
- **`app/Http/Controllers/RoulettesController.php`** — Pre-built curated collections (Netflix Horror, Anime, etc.).
- **`routes/web.php`** — All web routes; also defines AJAX routes.
- **`vite.config.js`** — Vite config; compiles `resources/js/custom/*.js` + `app.js` and Sass. jQuery is injected globally via `@rollup/plugin-inject`.

### Frontend

Most UI logic lives in `resources/js/custom/` as vanilla JS/jQuery. Bootstrap 4 handles layout; OWL Carousel, bootstrap-select, and SmartWizard are used for UI components. Dark/light theme switching is handled via CSS variables in `resources/sass/themes/`.

### Geolocation

`stevebauman/location` (IP geolocation) determines the user's country to filter TMDB watch providers so only locally available streaming options are shown. Country detection is used in `MovieService` before calling the TMDB watch providers endpoint.

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
