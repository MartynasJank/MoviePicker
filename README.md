# MoviePickr

> Stop scrolling. Let MoviePickr find something worth watching.

A Laravel app that picks random movies based on your mood — filter by genre, decade, streaming service, cast, rating, or skip the filters entirely and get surprised.

**Live:** https://moviepicker.martybuilds.dev/

---

## Features

- **Random pick** — one-click random movie, with or without criteria
- **Batch mode** — get a curated set of random picks at once
- **Criteria search** — filter by genre, year range, streaming provider, vote score, vote count, actor, or director
- **Roulettes** — curated collections (Netflix Horror, Documentaries, Anime) for quick picks
- **Movie detail page** — ratings from IMDb, Rotten Tomatoes, and Metacritic; cast & crew; trailer; similar movies; where to watch (filtered to your country)
- **Light / dark theme** — persisted across sessions

---

## Tech Stack

| Layer | Tools |
|---|---|
| Backend | Laravel 12, PHP 8.4 |
| Frontend | Bootstrap 4, Tailwind CSS 4, jQuery, Swiper, Tom Select, Vite |
| APIs | TMDB (movies, providers, trailers), OMDb (RT/Metacritic scores), YouTube Data API |
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

# Contact form (optional)
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
```

```bash
php artisan migrate
npm run dev
php artisan serve
```

App runs at `http://localhost:8000`.

---

## Deployment

Pushing to `master` triggers a GitHub Actions workflow that:

1. Builds frontend assets (`npm run build`)
2. SCPs the app to the VPS
3. SSHs in to run migrations and clear caches

Required GitHub secrets: `SSH_HOST`, `SSH_USER`, `SSH_KEY`.

The `.env` on the server is managed manually and never deployed from the repo.