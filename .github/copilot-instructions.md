# Copilot Instructions

## Build & Test Commands

```bash
# Install dependencies
composer install

# Run all PHPUnit tests
./vendor/bin/phpunit

# Run a single test file
./vendor/bin/phpunit tests/Unit/ClientTest.php

# Run a single test method
./vendor/bin/phpunit --filter it_validates_empty_messages

# Run only the Unit test suite
./vendor/bin/phpunit --testsuite Unit

# Run the standalone API integration test suite (requires real credentials in .env.test)
php tests/api-test-suite.php

# Run API tests in test mode (no real API calls)
MESSAGEMEDIA_TEST_MODE=true php tests/api-test-suite.php

# Run tests in Docker (PHP 7.3 compatibility)
docker-compose -f docker-compose.test.yml up --build
```

## Architecture

This is a zero-dependency Laravel package (no Guzzle/HTTP client libraries) that wraps the [MessageMedia Messages API](https://developers.messagemedia.com/). All HTTP is done via raw `ext-curl`.

**Request flow:**

1. Consumer creates a typed `Request\*` object (e.g. `SendMessagesRequest`) and populates its public properties
2. `Client` (registered as the `messagemedia` singleton) validates the request and delegates to `Http\HttpClient`
3. `HttpClient` builds the curl request with Basic auth (or HMAC if `use_hmac = true`) and returns a decoded array
4. `Client` wraps the array in a typed `Response\*` object via `static::fromArray()`

**Key classes:**

- `Client` — public API surface; all methods correspond to MessageMedia API endpoints
- `Http\HttpClient` — raw curl wrapper; handles auth headers, proxy, error mapping to exceptions
- `Message` — dual-purpose DTO: used both for outbound message parameters and inbound API responses (e.g. `getMessageStatus`)
- `ServiceProvider` — registers `Client` as a singleton using `config/messagemedia.php`; supports package auto-discovery

## Key Conventions

**Naming in PHP vs API wire format:** PHP properties use camelCase (`destinationNumber`, `messageId`), but the JSON wire format uses snake_case (`destination_number`, `message_id`). The mapping happens in `Message::fromArray()` / `Message::toArray()` and in `Client::messageToArray()`.

**`fromArray()` static constructor pattern:** Every Response class and `Message` use a static `fromArray(array $data)` factory method for construction from API responses. Prefer this over direct construction when hydrating from API data.

**Exception hierarchy:** All exceptions extend `MessageMediaException`. `ValidationException` carries a public `$errors` array of `['field' => ..., 'message' => ...]` structs. HTTP 401/403 → `AuthenticationException`, 404 → `NotFoundException`, 400/422 → `ValidationException`, other errors → `ApiException`.

**Client-side validation before HTTP:** `Client` validates request objects (required fields, phone number format `^\+?[0-9]{10,}$`) and throws `ValidationException` before any HTTP call is made.

**Two test mechanisms:**
- `tests/Unit/ClientTest.php` — PHPUnit unit tests, no network access, test credentials stubbed in `phpunit.xml`
- `tests/api-test-suite.php` — standalone PHP script for live API testing; reads credentials from `.env.test` (copy from `.env.test.example`)

**Proxy config:** Resolved via config key `messagemedia.proxy`, which falls back to the `HTTP_PROXY` environment variable if `MESSAGEMEDIA_PROXY` is not set.
