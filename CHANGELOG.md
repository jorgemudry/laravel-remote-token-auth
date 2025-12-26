# Changelog

All notable changes to `laravel-remote-token-auth` will be documented in this file

## 1.0.0 - 2025-12-26

### Added
- PHPStan static analysis step in CI workflow

### Changed
- **BREAKING:** Minimum PHP version updated from ^8.0 to ^8.3
- **BREAKING:** Minimum Laravel version updated to 11.x (via orchestra/testbench ^9.0)
- Updated Pest from v1.x to v3.x
- Updated PHPUnit from v9 to v11
- Updated laravel/pint to ^1.18
- Updated phpstan/phpstan to ^1.12
- Updated `AuthenticatedUser` ArrayAccess method signatures for PHP 8.3 compatibility
- Refactored `MakeValidationRequestActionTest` to use `Http::fake()` instead of Mockery alias mocking
- Updated GitHub Actions to test PHP 8.3/8.4 with Laravel 11
- Updated actions/checkout from v2 to v4

### Removed
- **BREAKING:** Removed `LumenServiceProvider` (Lumen is no longer maintained)
- Removed `pestphp/pest-plugin-mock` dependency (now bundled in Pest 2.x+)
- Removed support for PHP 8.0, 8.1, 8.2
- Removed support for Laravel 8.x, 9.x, 10.x

### Fixed
- Fixed typo in `GetAttributesFromResponseAction` parameter name (`$respose` -> `$response`)

## 0.x - Initial Release

- Initial release with Laravel 8.x-10.x and PHP 8.0+ support
