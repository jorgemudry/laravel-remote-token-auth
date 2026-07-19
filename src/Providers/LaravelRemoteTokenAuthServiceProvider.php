<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\CachedTokenValidator;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\CreateUserFromAttributesAction;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\GetAttributesFromResponseAction;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\MakeValidationRequestAction;
use JorgeMudry\LaravelRemoteTokenAuth\Adapter;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\AdapterInterface;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\CreatesUser;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\ResolvesAttributes;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\ResolvesToken;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\ValidatesToken;
use JorgeMudry\LaravelRemoteTokenAuth\Exceptions\MissingConfigurationException;
use JorgeMudry\LaravelRemoteTokenAuth\ValueObjects\AuthenticatedUser;

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

        // The adapter is resolved lazily, per authentication attempt, so apps
        // that never hit the guard pay no cost and rebinding the contract or
        // changing configuration is always picked up.
        Auth::viaRequest(
            'remote-token-auth',
            function (Request $request): Authenticatable {
                /** @var AdapterInterface $adapter */
                $adapter = $this->app->make(AdapterInterface::class);

                return $adapter->authorize($request);
            }
        );
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        if ( ! ($this->app instanceof CachesConfiguration && $this->app->configurationIsCached())) {
            $this->mergeConfigFrom(__DIR__ . '/../../config/remote-token-auth.php', 'remote-token-auth');
        }

        $this->registerGuard();
        $this->registerContracts();
        $this->registerAdapter();
    }

    protected function registerGuard(): void
    {
        $guard = $this->guardName();

        config([
            'auth.guards.' . $guard => array_merge([
                'driver' => 'remote-token-auth',
                'provider' => null,
            ], config('auth.guards.' . $guard, [])),
        ]);
    }

    /**
     * Bind each pipeline contract to the class configured under
     * "remote-token-auth.actions", validating that it honors the contract.
     * The cache decoration uses extend() so it also applies when an app
     * rebinds ValidatesToken with its own implementation.
     */
    protected function registerContracts(): void
    {
        $this->app->bind(
            ResolvesToken::class,
            fn (): ResolvesToken => $this->resolveConfiguredAction('token-resolver', ResolvesToken::class)
        );

        $this->app->bind(
            ValidatesToken::class,
            fn (): ValidatesToken => $this->resolveConfiguredAction('token-validator', ValidatesToken::class)
        );

        $this->app->extend(ValidatesToken::class, function (ValidatesToken $validator): ValidatesToken {
            if ( ! boolval(config('remote-token-auth.cache.enabled', false))) {
                return $validator;
            }

            $store = config('remote-token-auth.cache.store');

            return new CachedTokenValidator(
                $validator,
                $this->app->make(ResolvesAttributes::class),
                $this->app->make(CreatesUser::class),
                is_string($store) ? $store : null,
                intval(config('remote-token-auth.cache.ttl', 60)),
                strval(config('remote-token-auth.cache.prefix', 'remote-token-auth')),
            );
        });

        $this->app->bind(
            ResolvesAttributes::class,
            fn (): ResolvesAttributes => $this->resolveConfiguredAction('attributes-resolver', ResolvesAttributes::class)
        );

        $this->app->bind(
            CreatesUser::class,
            fn (): CreatesUser => $this->resolveConfiguredAction('user-maker', CreatesUser::class)
        );
    }

    protected function registerAdapter(): void
    {
        $this->app->bind(
            AdapterInterface::class,
            fn (): AdapterInterface => new Adapter(
                $this->app->make(ResolvesToken::class),
                $this->app->make(ValidatesToken::class),
                $this->app->make(ResolvesAttributes::class),
                $this->app->make(CreatesUser::class),
                $this->guardName(),
                $this->rejectionStatuses(),
            )
        );
    }

    /**
     * @return array<int, int>
     */
    protected function rejectionStatuses(): array
    {
        $statuses = config('remote-token-auth.http.rejection_statuses', [400, 401, 403]);

        if ( ! is_array($statuses)) {
            return [400, 401, 403];
        }

        return array_values(array_map(fn ($status): int => intval($status), $statuses));
    }

    /**
     * @template TContract of object
     * @param class-string<TContract> $contract
     * @return TContract
     */
    protected function resolveConfiguredAction(string $config_key, string $contract): object
    {
        $class = config('remote-token-auth.actions.' . $config_key);

        if ($class === null && $config_key === 'token-validator') {
            // 0.x name for the same step, honored so published configs from
            // before the rename keep working.
            $class = config('remote-token-auth.actions.request-maker');
        }

        if ( ! is_string($class) || $class === '') {
            throw new MissingConfigurationException(sprintf(
                'No class is configured for "remote-token-auth.actions.%s". '
                . 'If you upgraded from 0.x, republish the config file (the "request-maker" key is now "token-validator").',
                $config_key,
            ));
        }

        $action = $this->app->make($class, $this->actionParameters($class));

        if ( ! $action instanceof $contract) {
            throw new MissingConfigurationException(sprintf(
                'The class [%s] configured as "remote-token-auth.actions.%s" must implement %s.',
                $class,
                $config_key,
                $contract,
            ));
        }

        return $action;
    }

    /**
     * Constructor parameters for the configured action, matched by name so
     * they also reach subclasses of the default actions; classes that declare
     * different constructors simply ignore the unmatched ones.
     *
     * @return array<string, mixed>
     */
    protected function actionParameters(string $class): array
    {
        if (is_a($class, MakeValidationRequestAction::class, true)) {
            return [
                'endpoint' => $this->endpoint(),
                'timeout' => floatval(config('remote-token-auth.http.timeout', 5)),
                'connect_timeout' => floatval(config('remote-token-auth.http.connect_timeout', 2)),
            ];
        }

        if (is_a($class, GetAttributesFromResponseAction::class, true)) {
            return [
                'path' => strval(config('remote-token-auth.response.user_path')),
            ];
        }

        if (is_a($class, CreateUserFromAttributesAction::class, true)) {
            return [
                'user_class' => strval(config('remote-token-auth.response.user_class', AuthenticatedUser::class)),
            ];
        }

        return [];
    }

    protected function endpoint(): string
    {
        $endpoint = strval(config('remote-token-auth.endpoint'));

        if ($endpoint === '') {
            throw new MissingConfigurationException(
                'The "remote-token-auth.endpoint" configuration value is not set.'
            );
        }

        return $endpoint;
    }

    protected function guardName(): string
    {
        return strval(config('remote-token-auth.guard', 'rta'));
    }
}
