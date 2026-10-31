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
