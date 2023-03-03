<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\AdapterInterface;
use JorgeMudry\LaravelRemoteTokenAuth\ValueObjects\AuthenticatedUser;
use Throwable;

class LaravelRemoteTokenAuthAdapter implements AdapterInterface
{
    public function __construct(
        protected string $endpoint,
        protected string $path,
        protected string $user_class,
    ) {
    }

    /**
     * Authorize the user's token using an external service.
     *
     * @throws Exception
     * @throws AuthenticationException
     */
    public function authorize(Request $request): Authenticatable
    {
        try {
            $token = $request->bearerToken() ?? '';

            if (empty($token)) {
                throw new Exception('A bearer token is required.');
            }

            $response = Http::asJson()
                ->withToken($token)
                ->get($this->endpoint)
                ->throw()
                ->json();
        } catch (Throwable $th) {
            throw new AuthenticationException($th->getMessage());
        }

        $attributes = empty($this->path)
            ? $response
            : Arr::get($response, $this->path, []);

        /**
         * @var GenericUser $user
         * @todo Find a better way to pass static analysis
         */
        $user = empty($this->user_class)
            ? new AuthenticatedUser($attributes)
            : new $this->user_class($attributes);

        $implements = in_array(
            Authenticatable::class,
            class_implements($user)
        );

        return $implements === false
            ? new AuthenticatedUser($attributes)
            : $user;
    }
}
