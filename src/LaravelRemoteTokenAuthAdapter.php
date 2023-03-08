<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\ActionsResolver;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\AdapterInterface;
use JorgeMudry\LaravelRemoteTokenAuth\ValueObjects\AuthenticatedUser;
use Throwable;

class LaravelRemoteTokenAuthAdapter implements AdapterInterface
{
    public function __construct(
        protected string $endpoint,
        protected string $path,
        protected string $user_class,
        protected ActionsResolver $actions,
    ) {
    }

    /**
     * Authorize the user's token using an external service.
     *
     * @throws AuthenticationException
     */
    public function authorize(Request $request): Authenticatable
    {
        try {
            $token_resolver = $this->actions->getTokenResolver();
            $request_maker = $this->actions->getRequestMaker();
            $attributes_resolver = $this->actions->getAttributesResolver();

            $token = $token_resolver->execute($request);
            $response = $request_maker->execute($token, $this->endpoint);
            $attributes = $attributes_resolver->execute($response, $this->path);
        } catch (Throwable $th) {
            throw new AuthenticationException($th->getMessage());
        }

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
