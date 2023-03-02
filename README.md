# Laravel Remote Token Auth

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jorgemudry/laravel-remote-token-auth.svg?style=flat-square)](https://packagist.org/packages/jorgemudry/laravel-remote-token-auth)
[![Total Downloads](https://img.shields.io/packagist/dt/jorgemudry/laravel-remote-token-auth.svg?style=flat-square)](https://packagist.org/packages/jorgemudry/laravel-remote-token-auth)
![GitHub Actions](https://packagist.org/packages/jorgemudry/laravel-remote-token-auth/actions/workflows/main.yml/badge.svg)

This package provides a hassle-free way to incorporate authentication in your application by integrating with an external service.

It enables the validation of tokens to verify the authenticity of users, ensuring that only valid users gain access to your system.

With this package, you can focus on developing your application's core functionalities while the authentication process is handled seamlessly.

It simplifies the integration process, allowing you to obtain a valid user in no time.

## Requirements

- Laravel 8.x or higher
- PHP 8.0 or higher

## Installation

You can install the package via composer:

```bash
composer require jorgemudry/laravel-remote-token-auth
```
The package will automatically register its service provider.

You can publish the config file with:

```bash
php artisan vendor:publish php artisan vendor:publish --provider="JorgeMudry\LaravelRemoteTokenAuth\Providers\LaravelRemoteTokenAuthServiceProvider" --tag="config"
```

## Usage

To require authentication for a specific route, simply add the auth middleware and specify the *rta* guard. This will ensure that only authenticated users with a valid token can access the route.

```php
Route::get('/users', function (Request $request) {
    return $request->user();
})->middleware('auth:rta');
```

## Advanced Usage

To create a custom implementation of the class that calls the external API to validate the token, simply implement the AdapterInterface interface on your class. Then, bind the interface to your implementation in the register method of the AuthServiceProvider. This will allow you to use your own implementation of the class for token validation.

```php
<?php

namespace App\Adapters;

use JorgeMudry\LaravelRemoteTokenAuth\Contracts\AdapterInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

class MyAdapter implements AdapterInterface
{
    public function authorize(Request $request): Authenticatable
    {
        // your custom implementation logic
    }
}
```

```php
<?php

namespace App\Providers;

use App\Adapters\MyAdapter;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\AdapterInterface;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
    }

    /**
     * Register any application services.
     */
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
