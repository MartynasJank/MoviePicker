# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

MoviePickr is a Laravel 7 PHP application that recommends random movies based on user criteria (genre, year range, streaming provider, rating). It integrates with TMDB, OMDb, and YouTube APIs. The deployed app is at https://martyweb.lt/.

## Commands

All commands run from `moviepicker/` unless noted.

**Frontend:**
```bash
npm run dev          # development build
npm run watch        # dev build with file watching
npm run prod         # production minified build
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

The project root has two key directories:
- `moviepicker/` — the Laravel application (all source code lives here)
- `public_html/` — the web server document root; `public_html/index.php` bootstraps Laravel from `moviepicker/`

### Request Flow

1. `public_html/index.php` → loads `moviepicker/vendor/autoload.php` → bootstraps `moviepicker/bootstrap/app.php`
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
- **`webpack.mix.js`** — Compiles `resources/js/custom/*.js` + `app.js`, Sass, and copies fonts. jQuery is provided globally.

### Frontend

Vue 2 is used for reactive components (mounted in `resources/js/app.js`), but most UI logic lives in `resources/js/custom/` as vanilla JS/jQuery. Bootstrap 4 handles layout; OWL Carousel, bootstrap-select, and SmartWizard are used for UI components. Dark mode is handled via `darkmode-js`.

### Geolocation

`stevebauman/location` (IP geolocation) determines the user's country to filter TMDB watch providers so only locally available streaming options are shown. Country detection is used in `MovieService` before calling the TMDB watch providers endpoint.

## Environment Setup

Copy `moviepicker/.env.example` to `moviepicker/.env` and fill in:
- `TMDB_API_KEY` — TMDB API v3 key
- `OMDB_API_KEY` — OMDb API key
- `YOUTUBE_API_KEY` — YouTube Data API key
- MySQL credentials

API keys are accessed via `config/api.php`.

## Deployment

GitHub Actions (`.github/workflows/deploy.yml`) builds assets and deploys via SCP/SSH on push to `main`. Requires `SSH_HOST`, `SSH_USER`, and `SSH_KEY` secrets. The deploy target directory on the server is `/var/www/tiktokshuffle`.
