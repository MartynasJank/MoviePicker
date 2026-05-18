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
4. `MovieService` uses `app/Services/TmdbClient.php` (primary) or `app/Services/OmdbClient.php` (metadata enrichment)
5. Blade templates in `resources/views/` render the response

### Key Files

- **`app/Services/TmdbClient.php`** — TMDB API client (discover, trending, genres, watch providers, trailers, people). Bearer token auth + GuzzleHTTP. Implements `MovieApiInterface`.
- **`app/Services/OmdbClient.php`** — OMDb API client (Rotten Tomatoes scores, detailed plot) via cURL. Implements `MovieApiInterface`.
- **`app/Interfaces/MovieApiInterface.php`** — Interface enforcing `movie($id)` on both API clients.
- **`app/Services/MovieService.php`** — Core business logic: random movie selection, genre caching, page resolution, batch picking, streaming provider links, geolocation.
- **`app/Services/RatingsUrlBuilder.php`** — Builds URLs for IMDb, Rotten Tomatoes (with slug fallbacks), and Metacritic.
- **`app/Http/Controllers/MoviePickController.php`** — Handles single (`/movie`) and batch (`/multiple`) random movie requests.
- **`app/Http/Controllers/MovieController.php`** — Detailed movie view: ratings, cast, crew, watch providers, similar movies, trailers, reviews.
- **`app/Http/Controllers/RouletteController.php`** — Pre-built curated collections (Netflix Horror, Documentaries, Anime).
- **`app/Http/Controllers/HomeController.php`** — Homepage with trending movies from TMDB.
- **`app/Http/Controllers/CriteriaController.php`** — Renders search form with genres and streaming providers.
- **`app/Http/Controllers/UserInputController.php`** — `GET /userinput` returns session-stored form values as JSON (used by JS to repopulate the form on page load).
- **`app/Http/Controllers/TmdbProxyController.php`** — Server-side proxy for TMDB people search (`/tmdb/search/people`, `/tmdb/people/{id}`), keeping the API key off the browser.
- **`app/Http/Controllers/ContactController.php`** — Handles contact form POST to `/`, sends email via `ContactMail`.
- **`app/Http/Requests/CriteriaRequest.php`** — Validates search input: year range bounds (1874–current), vote average 0–10.
- **`app/Models/Click.php`** — Records each movie discovery: visitor hash (cookie), JSON input criteria, resulting movie ID. Used for analytics.
- **`routes/web.php`** — All web routes; includes obfuscated utility routes for cache clearing and scheduler triggering.

### Frontend

JS lives in `resources/js/custom/`, bundled by Vite. jQuery is injected globally via `@rollup/plugin-inject`.

- **`criteriaForm.js`** — 5-step criteria form using TomSelect for genre/provider dropdowns and Flexdatalist autocomplete for actor/crew/movie search (proxied through `/tmdb/search/people`).
- **`trailerModal.js`** — YouTube trailer modal with region restriction checking; also contains the step wizard for the "adjust criteria" modal.
- **`carousel.js`** — Three Swiper instances: similar movies, homepage trending, batch results.
- **`showMore.js`** — Expands truncated lists (cast, crew, production companies) with a "Show All" toggle.

Bootstrap 4 handles layout; Tailwind CSS 4 is also included. Dark/light theme switching uses CSS custom properties.

### Geolocation

`stevebauman/location` (IP geolocation) determines the user's country in `MovieService::getUserCountry()` to filter TMDB watch providers so only locally available streaming options are shown.

### Database

Two tables:
- **`users`** — Standard Laravel auth table (not actively used for authentication).
- **`clicks`** — Analytics: `visitor` (cookie hash), `input` (JSON search criteria), `result` (TMDB movie ID).

## Environment Setup

Copy `.env.example` to `.env` and fill in:
- `TMDB_API_KEY` — TMDB API v3 key
- `OMDB_API_KEY` — OMDb API key
- `YOUTUBE_API_KEY` — YouTube Data API key
- MySQL credentials

For production also set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://moviepicker.martybuilds.dev`.

API keys are accessed via `config/api.php`.

## Deployment

GitHub Actions (`.github/workflows/deploy.yml`) triggers on push to `master`:
1. Installs PHP 8.4 and Node 22
2. Runs `composer install --no-dev` and `npm run build`
3. SCPs app files to `/var/www/moviepicker` on the VPS
4. SSHs in to run `php artisan migrate --force`, `config:cache`, `route:cache`, `view:cache`

Required GitHub secrets: `SSH_HOST`, `SSH_USER`, `SSH_KEY`. The `.env` file is never deployed — it must be created manually on the server once.