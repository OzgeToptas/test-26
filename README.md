# Rick and Morty Encyclopedia

A Laravel 13 application for browsing characters from the public Rick and Morty REST API. Built for the PHP developer assessment; the original brief is preserved in [ASSESSMENT.md](ASSESSMENT.md).

## Features

- Paginated character listing with name search and status, species and gender filters.
- Character details with image, origin, last known location and episode names, codes and air dates.
- Responsive, server-rendered Blade pages with no JavaScript build required.
- Ten-minute caching of validated successful API responses.
- Controlled empty states, missing-character responses and external-service error messages.

## Requirements

- PHP 8.3+ with the extensions required by Composer, plus PDO SQLite for the default database.
- Composer 2 and Git.
- Internet access to `https://rickandmortyapi.com` for browsing uncached data and images.

Tests use fake HTTP responses and do not require access to the external API. Node.js, npm, API credentials and a dedicated Rick and Morty SDK are not required.

## Local setup

```sh
git clone https://github.com/OzgeToptas/test-26.git
cd test-26
composer install
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
php artisan serve
```

Open `http://127.0.0.1:8000`. If `.env` already exists, retain it instead of copying over it. The SQLite database stores framework session and cache data; characters remain in the external API. The default `.env.example` uses database sessions and caching, so the migrations are required.

The API base URL is configured with `RICK_AND_MORTY_API_URL`, defaulting to `https://rickandmortyapi.com/api`. It is a trusted deployment setting, never a user-supplied URL. After changing cached environment configuration, run `php artisan config:clear` locally. Clear stored API responses with `php artisan cache:clear` when needed.

## Architecture and decisions

- **CharacterController** validates query input and selects views and HTTP responses.
- **CharacterService** shapes API data for the views, handles empty searches and retrieves episode details in one bulk request.
- **RickAndMortyApiClient** implements API communication using Laravel's generic HTTP client, restricts endpoint paths, applies timeouts, validates consumed response fields and caches successful results.
- **Blade views** render escaped output and preserve filters in pagination links.
- **RickAndMortyApiException** carries controlled upstream failure codes between the client, service and controller.

The service and API client form the data-access part of MVC. No Eloquent character model is needed because characters are not persisted locally. Arrays keep this small application straightforward; dedicated DTOs would become useful if its data transformations grew.

Search, filtering and pagination run on the upstream API rather than loading all characters into memory. Character images load lazily on listing pages. Episode URLs are used only to extract numeric IDs; the server builds its own allowed API endpoint rather than requesting arbitrary returned URLs.

## Validation, errors and resilience

Query validation accepts positive integer pages, limits search/species strings to 100 characters and allows only supported status/gender values. Blade escapes displayed API data and input values.

Before caching, response validation checks pagination counts, list structure, positive integer IDs, consumed string fields, HTTP(S) image addresses and nested character/episode data. Invalid JSON, missing required fields and wrong field types become controlled upstream errors rather than view errors. Extra unused API fields are allowed.

The client uses a 3-second connection timeout and an 8-second request timeout. Successful validated responses are cached for 10 minutes. Failures are not cached. There are deliberately no automatic retries: this keeps request latency bounded and avoids repeating requests during rate limiting. A future retry policy should be limited to transient failures and account for `Retry-After`.

| Upstream outcome | Application behavior |
| --- | --- |
| Character listing returns 404 | Empty search/page result, HTTP 200 |
| Character detail returns 404 | Character-not-found page, HTTP 404 |
| Upstream 429 | Client exception 429; browser receives HTTP 503 |
| Connection failure/timeout | Client exception 503; browser receives HTTP 503 |
| Other upstream HTTP errors or malformed data | Client exception 502; browser receives HTTP 503 |

The browser receives a generic unavailable message for service failures; diagnostic information is logged server-side. The `/up` health endpoint checks application boot, independently of external API availability.

## Checks

```sh
composer test
vendor/bin/pint --test
composer audit --locked
composer check-platform-reqs
git diff --check
```

Tests cover query validation, listing and pagination, filtering, character details and bulk episodes, caching, missing resources, rate limiting, connection failures, malformed responses and upstream server errors. The test suite blocks unintended real HTTP requests and uses an in-memory cache/session configuration.

## Production configuration

Use a separate production environment with at least:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
SESSION_SECURE_COOKIE=true
LOG_LEVEL=warning
```

Generate a unique production `APP_KEY`, keep `.env` out of Git, serve only the `public/` directory over HTTPS and allow the web process to write to `storage/` and `bootstrap/cache/`. Install locked dependencies with `composer install --no-dev --optimize-autoloader`, prepare the configured database and run `php artisan migrate --force`, then `php artisan config:cache` and `php artisan view:cache`.

Local `.env.example` intentionally enables debug mode for development. Do not deploy with that setting unchanged. On multiple application servers, use a shared database or Redis cache/session store. Caching reduces repeated calls but does not provide a global rate limiter, cache-stampede protection or stale responses during outages; those are possible follow-up improvements rather than implemented features.

## Attribution

Character and episode data and images are provided by the [Rick and Morty API](https://rickandmortyapi.com/). All trademarks remain the property of their respective owners.
