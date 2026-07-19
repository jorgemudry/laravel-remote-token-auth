# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

Laravel package (`jorgemudry/laravel-remote-token-auth`) that adds a stateless auth guard (`rta` by default, configurable) which validates bearer tokens against an external HTTP API. Supports PHP 8.1+ and Laravel 10–12 (tested via Orchestra Testbench, local dev runs Laravel 12 / Pest 3 / PHPStan 2).

## Commands

```bash
composer test                      # Run the full test suite (Pest)
vendor/bin/pest --filter="name"    # Run a single test by name
vendor/bin/pest tests/Unit/AdapterTest.php   # Run a single test file
composer pint                      # Code style (Laravel Pint, PSR-12 preset + custom rules in pint.json)
composer stan                      # Static analysis (PHPStan level 8 on src/)
```

All three (tests, pint, stan) are expected to pass before submitting changes; CI runs Pest across a PHP (8.1–8.4) / Laravel (10–12) matrix.

## Architecture

The auth flow is a pipeline of four single-purpose steps, each defined by a contract in `src/Contracts/` and orchestrated by `src/Adapter.php`:

1. `ResolvesToken` → `GetTokenFromRequestAction` — extracts the bearer token
2. `ValidatesToken` → `MakeValidationRequestAction` — GETs the external endpoint with the token (timeouts from `http.*` config)
3. `ResolvesAttributes` → `GetAttributesFromResponseAction` — pulls user attributes via the configured `response.user_path`; throws if empty
4. `CreatesUser` → `CreateUserFromAttributesAction` — instantiates `response.user_class`; throws if the auth identifier is missing

Key design points:

- **Container is the extension mechanism**: the service provider binds each contract to the class named under `remote-token-auth.actions.*` (validating it implements the contract; the legacy `request-maker` key is honored as a fallback). Users swap a step via config or by rebinding the contract. The whole pipeline can be replaced by rebinding `AdapterInterface`.
- **Default actions carry their config in constructors** (endpoint, path, user class); `execute()` only takes pipeline data. Config is passed as make-time parameters in `resolveConfiguredAction()`/`actionParameters()`, matched by constructor parameter name so it also reaches subclasses of the default actions — a bare `app(MakeValidationRequestAction::class)` outside that path fails (required constructor args). Timeouts are floats passed via Guzzle `withOptions` (PendingRequest::timeout() is int-typed on Laravel 10).
- **Error split is deliberate** (`Adapter::authorize()`): a missing token (`InvalidTokenException` — never catch bare `InvalidArgumentException`, it swallows framework errors) or a rejection status from the service (default 400/401/403, configurable via `http.rejection_statuses` — real services do use 400 for bad tokens) → `AuthenticationException` (401) with the fixed generic message "Invalid access token." (never leak internals). Everything else — Illuminate/Guzzle connection failures, 5xx, AND unexpected statuses (404/405/429) → `ValidationServiceUnavailableException` (503, extends Symfony HttpException), always `report()`ed. Custom validators may throw the 503 exception directly (documented on the `ValidatesToken` contract).
- **Empty response ≠ authenticated user**: empty attributes or a null auth identifier throw `InvalidUserResponseException` (→ 401). Do not weaken this — it was a real auth-bypass bug in 0.x. The identifier check reads the attributes array for `GenericUser`-based classes (GenericUser's `getAuthIdentifier()` has no missing-key guard).
- **Caching**: `CachedTokenValidator` decorates `ValidatesToken` via `$this->app->extend()` (so app-level rebinds keep the decoration) when `cache.enabled` is true. Keyed by SHA-256 of the token. Two invariants: a response is only cached after the attributes-resolver AND user-maker accept it, and any cache-store failure is reported and falls back to direct validation — a cache problem must never reject a valid token.
- **Adapter resolves lazily** inside the `Auth::viaRequest` closure — never resolve it eagerly in `boot()` (Octane/config staleness).
- **Runtime deps are declared**: `illuminate/*` 10–12 + guzzle + symfony/http-kernel in composer.json. Keep constraints in sync with the CI matrix when adding Laravel versions.

## Conventions

- `declare(strict_types=1)` in every PHP file; style enforced by pint.json. `mb_str_functions` must stay disabled — Pint would rewrite `trim` to `mb_trim` (PHP 8.4-only), breaking the supported PHP range.
- Tests use Pest 3, no Mockery: feature tests exercise the real pipeline with `Http::fake()`; unit tests use real objects or small anonymous-class stubs of the contracts. `tests/Pest.php` provides a `createRequest()` helper; fixtures live in `tests/Fixtures/`.
- In feature tests, Laravel's `RequestGuard` caches the user across in-test requests — call `app('auth')->forgetGuards()` between requests when asserting per-request behavior.
- Follows SemVer; behavior changes must be reflected in README.md and CHANGELOG.md.
