<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\AdapterInterface;
use JorgeMudry\LaravelRemoteTokenAuth\LaravelRemoteTokenAuthAdapter;

class LaravelRemoteTokenAuthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes(
                [
                    __DIR__ . '/../../config/remote-token-auth.php' => config_path('remote-token-auth.php'),
                ],
                'remote-token-auth'
            );
        }

        $adapter = $this->app->make(AdapterInterface::class);

        /* @phpstan-ignore-next-line */
        Auth::viaRequest(
            'remote-token-auth',
            fn (Request $request): Authenticatable => $adapter->authorize($request)
        );
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        config([
            'auth.guards.rta' => array_merge([
                'driver' => 'remote-token-auth',
                'provider' => null,
            ], config('auth.guards.rta', [])),
        ]);

        if (app()->configurationIsCached() === false) {
            $this->mergeConfigFrom(__DIR__ . '/../../config/remote-token-auth.php', 'remote-token-auth');
        }

        $this->registerAdapter();
    }

    protected function registerAdapter(): void
    {
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
