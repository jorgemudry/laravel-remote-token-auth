<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\AdapterInterface;

class LumenServiceProvider extends LaravelRemoteTokenAuthServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $adapter = $this->app->make(AdapterInterface::class);

        /* @phpstan-ignore-next-line */
        $this->app['auth']->viaRequest(
            'remote-token-auth',
            fn (Request $request): Authenticatable => $adapter->authorize($request)
        );
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        /* @phpstan-ignore-next-line */
        $this->app->configure('auth');
        /* @phpstan-ignore-next-line */
        $this->app->configure('remote-token-auth');

        config([
            'auth.guards.rta' => array_merge([
                'driver' => 'remote-token-auth',
            ], config('auth.guards.rta', [])),
        ]);

        $this->registerAdapter();
    }
}
