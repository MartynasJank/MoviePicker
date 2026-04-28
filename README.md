# MoviePickr

A Laravel application that finds the perfect movie for you based on genre, year range, streaming provider, and rating.

Check it out at: https://moviepicker.martybuilds.dev/

## Tech Stack

- **Backend:** Laravel 12, PHP 8.4
- **Frontend:** Bootstrap 4, jQuery, OWL Carousel, Vite
- **APIs:** TMDB, OMDb, YouTube Data API
- **Deployment:** GitHub Actions → SCP to VPS

## Local Setup

1. Clone the repo
2. Copy `.env.example` to `.env` and fill in your API keys and DB credentials
3. Install dependencies:
```bash
composer install
npm install
```
4. Generate app key and run migrations:
```bash
php artisan key:generate
php artisan migrate
```
5. Start the dev server:
```bash
npm run dev
php artisan serve
```
