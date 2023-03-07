<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\AdapterInterface;
use JorgeMudry\LaravelRemoteTokenAuth\LaravelRemoteTokenAuthAdapter;

class LumenServiceProvider extends ServiceProvider
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

        $this->app->bind(
            AdapterInterface::class,
            function (): LaravelRemoteTokenAuthAdapter {
                $endpoint = strval(config('remote-token-auth.endpoint'));
                $path = strval(config('remote-token-auth.response.user_path'));
                $user_class = strval(config('remote-token-auth.response.user_class'));

                return new LaravelRemoteTokenAuthAdapter($endpoint, $path, $user_class);
            }
        );
    }
}
