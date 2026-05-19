# MoviePickr Feature Plan

## 1. OAuth + Watchlist

Allow users to sign in and save movies they want to watch.

**Auth**
- Laravel Socialite with Google OAuth
- Add `provider` and `provider_id` to `users` table
- Guest users see a "Sign in to save" prompt

**Watchlist**
- New `watchlist` table: `user_id`, `tmdb_id`, `status` (saved/watched), `genre`, `year`, `runtime`, `mood_tags`, `personal_rating`, `notes`
- Add/remove button on movie detail page and on pick result
- Dedicated `/watchlist` page showing saved movies in a filterable grid

---

## 2. Mood Shortcuts

Quick-pick from the homepage without going through the full 5-step form.

- Homepage section: "I want something…" with tiles (Funny, Intense, Feel-good, Dark, Romantic, Mindless)
- Each tile pre-fills the criteria form and submits immediately
- Implemented as simple query params that `CriteriaRequest` / `MovieService` already understand

---

## 3. Roulette Page Improvements

Make the roulette page more visual and scalable.

- **Poster grid** — hardcode 3–4 representative `tmdb_id`s per collection; fetch poster URLs from TMDB at page load via `TmdbClient`, cache the response. No local image storage needed.
- **Grouping** — organise collections by mood, decade, platform, genre with tabs or a sidebar
- **Spin animation** — brief shuffle/reveal before showing the picked movie
- **Custom roulettes** (post-auth) — logged-in users can create, name, and share their own collections

---

## Suggested Build Order

1. **Mood shortcuts** — self-contained, no dependencies ✓
2. **OAuth + Watchlist** — foundational for personalisation
3. **Roulette improvements** — custom roulettes depend on auth being done first
