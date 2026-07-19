# Laravel Remote Token Auth

<p align="center">
<a href="https://github.com/jorgemudry/laravel-remote-token-auth/actions"><img src="https://img.shields.io/github/actions/workflow/status/jorgemudry/laravel-remote-token-auth/main.yml?label=build" alt="Build Status"></a>
<a href="https://packagist.org/packages/jorgemudry/laravel-remote-token-auth"><img src="https://img.shields.io/packagist/dt/jorgemudry/laravel-remote-token-auth" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/jorgemudry/laravel-remote-token-auth"><img src="https://img.shields.io/packagist/v/jorgemudry/laravel-remote-token-auth" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/jorgemudry/laravel-remote-token-auth"><img src="https://img.shields.io/packagist/l/jorgemudry/laravel-remote-token-auth" alt="License"></a>
<a href="https://github.com/jorgemudry/laravel-remote-token-auth"><img src="https://img.shields.io/github/stars/jorgemudry/laravel-remote-token-auth" alt="Stars"></a>
</p>

This package provides a hassle-free way to incorporate authentication in your application when token validation happens in an external service.

It registers a stateless auth guard (`rta`) that validates the request's bearer token against an external API and builds the authenticated user from the response, so only valid users gain access to your endpoints.

Every step of the process is replaceable, so you can adapt the package to whatever your validation service looks like.

## Requirements

- PHP 8.1 or higher
- Laravel 10, 11 or 12

## Installation

You can install the package via composer:

```bash
composer require jorgemudry/laravel-remote-token-auth
```

The package will automatically register its service provider.

Set the validation endpoint in your `.env` file:

```dotenv
REMOTE_TOKEN_AUTH_ENDPOINT=https://your-auth-service.example/validate
```

Optionally, publish the config file to customize the rest:

```bash
php artisan vendor:publish --tag=remote-token-auth
```

## Usage

To require authentication for a route, add the auth middleware with the *rta* guard:

```php
Route::get('/users', function (Request $request) {
    return $request->user();
})->middleware('auth:rta');
```

On every request the package will:

1. Extract the bearer token from the `Authorization` header.
2. Send it (as a bearer token, in a GET request) to the configured endpoint.
3. Extract the user attributes from the JSON response, using the configured `response.user_path`.
4. Build the authenticated user, using the configured `response.user_class`.

### Error handling

Failures are split into two groups, so clients can tell them apart:

- **401 Unauthorized** — the token is missing, malformed, or the validation service explicitly rejected it (a 400, 401 or 403 response by default; configurable via `http.rejection_statuses`). The response body carries a generic message; internal details are never exposed and are written to the application log instead.
- **503 Service Unavailable** — the validation service is unreachable, timed out, returned a 5xx error, or answered with an unexpected status (404 from a mistyped endpoint, 429 rate limiting, ...). The client's token may still be perfectly valid, so it should retry later instead of discarding it. These failures are always reported to the application log.

## Configuration

```php
return [
    // Name of the guard registered by the package.
    'guard' => 'rta',

    // Endpoint of the external service that validates the token.
    'endpoint' => env('REMOTE_TOKEN_AUTH_ENDPOINT', ''),

    // Timeouts (in seconds, fractions allowed) for the validation request,
    // and the statuses that mean "token rejected" rather than "service down".
    'http' => [
        'timeout' => env('REMOTE_TOKEN_AUTH_TIMEOUT', 5),
        'connect_timeout' => env('REMOTE_TOKEN_AUTH_CONNECT_TIMEOUT', 2),
        'rejection_statuses' => [400, 401, 403],
    ],

    'response' => [
        // Dot-notation path to the user attributes inside the response.
        'user_path' => '',
        // Class used to represent the authenticated user.
        'user_class' => AuthenticatedUser::class,
    ],

    // Optional cache for successful validations (see below).
    'cache' => [
        'enabled' => env('REMOTE_TOKEN_AUTH_CACHE', false),
        'store' => null,
        'ttl' => 60,
        'prefix' => 'remote-token-auth',
    ],

    // The four steps of the authentication pipeline (see "Advanced Usage").
    'actions' => [
        'token-resolver' => GetTokenFromRequestAction::class,
        'token-validator' => MakeValidationRequestAction::class,
        'attributes-resolver' => GetAttributesFromResponseAction::class,
        'user-maker' => CreateUserFromAttributesAction::class,
    ],
];
```

### Caching validations

By default every authenticated request triggers one HTTP call to the validation service. If that becomes a problem (latency, rate limits), enable the cache:

```dotenv
REMOTE_TOKEN_AUTH_CACHE=true
```

Successful validations are cached (keyed by a SHA-256 hash of the token, in the configured `cache.store`) for `cache.ttl` seconds. A response is only cached after the whole pipeline accepts it (attributes resolved, user built), so failed or malformed validations are never cached. If the cache store itself fails or is misconfigured, the package reports the error and falls back to validating directly against the service — a cache outage never rejects valid tokens.

**Trade-off:** a token revoked in the external service keeps working in your application until the TTL expires. Keep the TTL short.

## Advanced Usage

### Replacing a single step of the pipeline

Each step is a small class bound to a contract from `JorgeMudry\LaravelRemoteTokenAuth\Contracts`:

| Config key | Contract | Default behavior |
| --- | --- | --- |
| `token-resolver` | `ResolvesToken` | Reads the bearer token from the request |
| `token-validator` | `ValidatesToken` | GETs the endpoint with the token, returns the JSON body |
| `attributes-resolver` | `ResolvesAttributes` | Extracts the attributes at `response.user_path` |
| `user-maker` | `CreatesUser` | Instantiates `response.user_class` with the attributes |

To replace a step, implement the matching contract and point the config key to your class. For example, a validator that POSTs the token instead:

```php
<?php

namespace App\Auth;

use Illuminate\Support\Facades\Http;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\AccessTokenInterface;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\ValidatesToken;

class PostValidationAction implements ValidatesToken
{
    public function execute(AccessTokenInterface $token): array
    {
        return Http::acceptJson()
            ->timeout(5)
            ->asForm()
            ->post(config('services.auth.introspection_url'), ['token' => $token->token()])
            ->throw()
            ->json();
    }
}
```

```php
// config/remote-token-auth.php
'actions' => [
    // ...
    'token-validator' => App\Auth\PostValidationAction::class,
],
```

Your class is resolved through the container, so you can inject any dependency in its constructor. If you instead **extend one of the default actions** and keep its constructor, the standard configuration (endpoint, timeouts, `user_path`, `user_class`) is passed to your subclass automatically.

**Signaling errors from a custom validator:** throw `ValidationServiceUnavailableException` when the service is unreachable or failing (renders as 503, clients keep their tokens) and `Illuminate\Auth\AuthenticationException` when the token was rejected (renders as 401). If you use Laravel's HTTP client or Guzzle, their connection exceptions — and any error response whose status is not in `http.rejection_statuses` — are mapped to the 503 path automatically.

### Using your own authenticated user class

Your class must implement `Illuminate\Contracts\Auth\Authenticatable` and accept the attributes array as its only constructor argument. The easiest way is extending the package's `AuthenticatedUser` (or Laravel's `GenericUser`):

```php
<?php

namespace App\Auth;

use JorgeMudry\LaravelRemoteTokenAuth\ValueObjects\AuthenticatedUser;

class ApiUser extends AuthenticatedUser
{
    public function isAdmin(): bool
    {
        return $this->getAttribute('role') === 'admin';
    }
}
```

```php
// config/remote-token-auth.php
'response' => [
    'user_path' => 'data',
    'user_class' => App\Auth\ApiUser::class,
],
```

### Replacing the whole adapter

If you need full control, implement `AdapterInterface` and rebind it in a service provider:

```php
<?php

namespace App\Providers;

use App\Adapters\MyAdapter;
use Illuminate\Support\ServiceProvider;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\AdapterInterface;

class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AdapterInterface::class, MyAdapter::class);
    }
}
```

## Testing

To run the package's test suite, run the following command:

```bash
composer test
```

## Upgrading from 0.x

- PHP 8.1+ and Laravel 10+ are now required.
- The Lumen service provider was removed.
- `ActionsResolver` was removed; the pipeline steps are now bound to contracts in the container. If you extended one of the default actions, implement the matching contract instead.
- The actions receive their configuration via constructor and their `execute()` methods take only the pipeline data.
- The `actions.request-maker` config key was renamed to `actions.token-validator`.
- Empty or identifier-less user attributes now reject the request instead of authenticating an empty user.
- Validation service outages now surface as 503 responses instead of 401.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

If you would like to contribute to the package, please see [CONTRIBUTING](CONTRIBUTING.md) for information on how to get started.

## Security

If you discover any security related issues, please email jorgemudry@gmail.com instead of using the issue tracker.

## Credits

-   [Jorge Mudry](https://github.com/jorgemudry)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
