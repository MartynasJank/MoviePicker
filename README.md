# MoviePickr

> Stop scrolling. Let MoviePickr find something worth watching.

A Laravel app that picks random movies based on your mood — filter by genre, decade, streaming service, cast, rating, or skip the filters entirely and get surprised.

**Live:** https://moviepicker.martybuilds.dev/

---

## Features

- **Random pick** — one-click random movie, with or without criteria
- **Batch mode** — get a curated set of random picks at once
- **Criteria search** — filter by genre, year range, streaming provider, vote score, vote count, actor, or director
- **Roulettes** — 140+ curated collections grouped by platform, decade, genre, language, and anime; DB-driven ordering
- **My Roulettes** — logged-in users can create personal roulettes with full tag freedom, organise them into custom rows, and optionally make them public
- **Movie detail page** — ratings from IMDb, Rotten Tomatoes, and Metacritic; cast & crew; trailer; similar movies; where to watch (filtered to your country)
- **TV show detail page** — same ratings coverage (IMDb, RT, Metacritic); seasons; cast & crew; trailer; similar shows; where to watch
- **Watchlist** — save movies to watch later, mark as watched, roll a random pick from your list
- **Roll animation** — animated strip reveal when rolling from watchlist, roulettes, batch pages, or the homepage
- **Admin dashboard** — manage roulettes and row order, view and moderate user-created roulettes
- **Light / dark theme** — persisted across sessions

---

## Tech Stack

| Layer | Tools |
|---|---|
| Backend | Laravel 12, PHP 8.4 |
| Frontend | Bootstrap 4, Tailwind CSS 4, jQuery, Swiper, SortableJS, Vite |
| APIs | TMDB (movies, providers, trailers), OMDb (RT/Metacritic scores), YouTube Data API |
| Auth | Google OAuth via Laravel Socialite |
| Deployment | GitHub Actions → SCP to VPS |

---

## Local Setup

**Requirements:** PHP 8.4, Composer, Node 22, MySQL

```bash
git clone https://github.com/MartynasJank/MoviePicker.git
cd MoviePicker
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Fill in `.env`:

```env
# Database
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_password

# API keys
TMDB_API_KEY=        # tmdb.org → Settings → API
OMDB_API_KEY=        # omdbapi.com
YOUTUBE_API_KEY=     # console.cloud.google.com → YouTube Data API v3

# Auth
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback

# Admin
ADMIN_EMAIL=         # Google account email that gets admin access
```

```bash
php artisan migrate
php artisan db:seed
npm run dev
php artisan serve
```

App runs at `http://localhost:8000`.

---

## AI-assisted development

Laravel Boost is installed as a development dependency. Its generated instructions
and skills support Codex and Claude Code. Project conventions live in
`.ai/guidelines/moviepicker.md`; edit that source and regenerate with:

```bash
composer ai:update
```

After cloning, run `composer install`, then `php artisan boost:install` to configure
the coding agents you use on your machine. Restart the agent session after MCP
configuration changes. Check that `laravel-boost` connects and can resolve a project URL. Codex configuration is in `.codex/config.toml`;
Claude Code uses `.mcp.json`. Keep existing local IDE connections when configuring
agents. Local PHP must be on PATH. Production targets PHP 8.4.

Before handing off a change:

```bash
composer test          # Pest, SQLite in memory, no MySQL required
npm run build          # compile and validate frontend assets
```

Mock external API clients in tests. For UI changes, also inspect the affected page
in the browser and check its console. CI runs tests and builds assets on pull
requests, and deployment waits for those checks to pass.

### Local development with Herd

Herd serves this linked project at `http://moviepicker.test`. On this Mac, start
MySQL with `brew services start mysql@8.4`. For a new checkout, copy `.env.example`
to `.env`, set `APP_URL=http://moviepicker.test` and your database credentials,
then generate the app key and run migrations. Create the configured database if
needed. Seed a new development database with `php artisan db:seed`.

Run `npm run dev` for frontend hot reload. For collaborative batches, configure
the Reverb values in `.env` and run `php artisan reverb:start` in another terminal.
Without Herd, use `php artisan serve` and set APP_URL to its URL instead.

If the site fails, check that Herd and MySQL are running, the app key is set, and
`storage/logs/laravel.log` has no new errors. After changing cached configuration,
run `php artisan config:clear`. Never paste credentials or full `.env` contents
into issue reports or AI prompts.

---

## Deployment

Pushing to `master` triggers a GitHub Actions workflow that:

1. Builds frontend assets (`npm run build`)
2. SCPs the app to the VPS
3. SSHs in to run migrations and clear caches

Required GitHub secrets: `SSH_HOST`, `SSH_USER`, `SSH_KEY`.

The `.env` on the server is managed manually and never deployed from the repo.
