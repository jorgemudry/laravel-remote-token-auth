# Changelog

All notable changes to `laravel-remote-token-auth` will be documented in this file

## 1.0.0 - 2026-07-19

First stable release. Breaking changes against the 0.x series:

### Added

- The `response.user_class` configuration is now honored when building the authenticated user.
- Contracts for each step of the pipeline (`ResolvesToken`, `ValidatesToken`, `ResolvesAttributes`, `CreatesUser`) so any step can be replaced with a standalone class.
- Configurable guard name (`guard` config key).
- Configurable timeouts for the validation request (`http.timeout`, `http.connect_timeout`); fractional seconds are supported.
- Optional cache for successful validations (`cache.*` config keys). Responses are only cached after the whole pipeline accepts them, and cache-store failures fall back to direct validation instead of rejecting tokens.
- A clear `MissingConfigurationException` when the endpoint or an action class is not configured, or a configured class does not honor its contract. The legacy `actions.request-maker` key is still honored as a fallback for stale published configs.
- Configuration flows automatically to subclasses of the default actions that keep the parent constructor.

### Changed

- Requires PHP 8.1+ and Laravel 10, 11 or 12.
- Validation service failures now produce a 503 response instead of a 401, and are reported to the application log. Only a configured rejection status (default 400/401/403, `http.rejection_statuses`) rejects the token; unreachable/timeout, 5xx, and unexpected statuses (404, 405, 429, ...) all take the 503 path. Guzzle's `ConnectException` is mapped alongside Laravel's HTTP client exceptions, and custom validators can throw `ValidationServiceUnavailableException` directly.
- A missing bearer token now raises the package's `InvalidTokenException` (extends `InvalidArgumentException`), so unrelated `InvalidArgumentException`s are reported instead of silently treated as bad tokens.
- Authentication failures respond with a generic message; internal error details are no longer leaked to clients.
- Empty user attributes or attributes without an auth identifier now reject the request instead of authenticating an empty user.
- The adapter is resolved lazily, once per authentication attempt.
- The `actions.request-maker` config key was renamed to `actions.token-validator`.
- `AuthenticatedUser::offsetExists()` now reports attributes that exist with a `null` value.

### Removed

- The Lumen service provider.
- `ActionsResolver`; pipeline steps are bound to their contracts in the container.

## 0.1.0 - 2023-03-01

- initial release
