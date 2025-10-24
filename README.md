# Drug Tracker API

Laravel 12 REST API that lets users search RxNorm medications and manage a personal medication list with secure authentication, caching, and rate limiting. The service integrates with the National Library of Medicine's RxNorm API and ships with automated tests, retry/circuit-breaker safeguards, and Postman assets for manual verification.

## Features
- **Authentication**: Laravel Sanctum token auth with registration and login endpoints.
- **Drug Search**: Public search endpoint backed by an RxNorm integration, resource transformers, and Redis caching.
- **Medication Management**: Authenticated CRUD for a user's medication list with duplicate protection and RXCUI validation.
- **Resilience**: Configurable retries with exponential backoff plus a Redis-backed circuit breaker for upstream RxNorm outages.
- **Rate Limiting**: Distinct throttles for public and authenticated routes via custom middleware.
- **Testing**: 49 passing PHPUnit tests (unit + feature + integration) covering services, repositories, middleware, and end-to-end flows.
- **Tooling**: Laravel Pint for linting, Postman collection/environment, Docker compose for local stack, and extensive configuration via `.env`.

## Tech Stack
- PHP 8.2, Laravel 12, Sanctum
- MySQL 8, Redis
- Guzzle HTTP client
- PHPUnit & Mockery
- Docker / Docker Compose (optional)

## Local Setup
1. **Install dependencies**
	```bash
	composer install
	npm install
	```

2. **Environment**
	```bash
	cp .env.example .env
	php artisan key:generate
	```

3. **Configure services**
	- Set your database credentials in `.env` (MySQL or SQLite for quick tests).
	- Configure Redis if using caching/rate limiting (falls back to array cache in testing).
	- Adjust RxNorm settings as needed (see [Configuration](#configuration)).

4. **Database**
	```bash
	php artisan migrate
	```

5. **Run the API**
	```bash
	php artisan serve
	```
	The API is available at `http://localhost:8000/api`.

### Docker (optional)
The repository includes `docker-compose.yml` for PHP, MySQL, and Redis.
```bash
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app php artisan migrate
```

## Configuration
Key environment variables from `.env.example`:

| Variable | Description | Default |
| --- | --- | --- |
| `APP_URL` | Base application URL | `http://localhost:8000` |
| `DB_*` | Database connection settings | MySQL service defaults |
| `CACHE_STORE` | Cache driver (`redis`, `array`, etc.) | `redis` |
| `RXNORM_API_URL` | RxNorm REST base URL | `https://rxnav.nlm.nih.gov/REST/` |
| `RXNORM_API_TIMEOUT` | HTTP timeout (seconds) | `10` |
| `RXNORM_CACHE_TTL` | Cache TTL for RxNorm responses (seconds) | `86400` |
| `RXNORM_RETRY_ATTEMPTS` | Retry attempts for RxNorm calls | `3` |
| `RXNORM_RETRY_DELAY_MS` | Initial retry delay (ms) | `200` |
| `RXNORM_RETRY_BACKOFF` | Exponential backoff multiplier | `2` |
| `RXNORM_CIRCUIT_FAILURE_THRESHOLD` | Failures before circuit opens | `5` |
| `RXNORM_CIRCUIT_COOLDOWN` | Circuit cooldown (seconds) | `60` |
| `RATE_LIMIT_PUBLIC` | Requests per minute for public search | `60` |
| `RATE_LIMIT_AUTHENTICATED` | Requests per minute for authed APIs | `120` |

## Testing & Quality
Run the full suite (unit, feature, integration):
```bash
php artisan test
```

Run with coverage:
```bash
php artisan test --coverage
```

Code style (Laravel Pint):
```bash
./vendor/bin/pint
```

## API Overview
| Method | Endpoint | Auth | Description |
| --- | --- | --- | --- |
| `POST` | `/api/auth/register` | ❌ | Create a user and return Sanctum token |
| `POST` | `/api/auth/login` | ❌ | Login and obtain a token |
| `POST` | `/api/auth/logout` | ✅ | Revoke current token |
| `GET` | `/api/search/drugs` | ❌ | Search RxNorm by `drug_name` query |
| `GET` | `/api/medications` | ✅ | List current user's medications |
| `POST` | `/api/medications` | ✅ | Add medication by `rxcui` (validated via RxNorm) |
| `DELETE` | `/api/medications/{rxcui}` | ✅ | Remove medication |

### Rate Limiting
- Public search: `RATE_LIMIT_PUBLIC` per minute, keyed by IP.
- Authenticated routes: `RATE_LIMIT_AUTHENTICATED` per minute, keyed by user ID (fallback to IP).
- Responses include `retry_after` seconds when throttled.

### Caching & Resilience
- RxNorm search and detail responses cached via `App\Cache\RxNormCacheManager`.
- Retries: Exponential backoff using configuration above.
- Circuit breaker: Redis-backed counters open the circuit after consecutive failures and short-circuit calls for the cooldown window.

## Postman Collection
Import `postman/drug-tracker-api.postman_collection.json` and the matching environment in `postman/environments/`. The collection mirrors automated tests and stores tokens automatically after login.

## Project Structure Highlights
- `app/Services/RxNormService.php`: Handles external calls, caching, retry, and circuit breaker.
- `app/Services/MedicationService.php`: Business logic for medication CRUD.
- `app/Repositories/MedicationRepository.php`: Eloquent repository abstraction for `user_medications`.
- `app/Http/Middleware/*RateLimiter.php`: Custom throttling per route type.
- `tests/Feature/CompleteMedicationFlowTest.php`: Full user happy-path integration test.
- `tests/Unit/Services/*`: Unit coverage for service classes.

## Contributing
1. Fork and clone the repository.
2. Create a topic branch: `git checkout -b feature/your-change`.
3. Add tests for your change and run `php artisan test`.
4. Run Pint for style consistency.
5. Submit a PR with context and screenshots/logs as relevant.

## License
MIT License. See `LICENSE` for details.
