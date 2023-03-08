<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\ActionsResolver;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\AdapterInterface;
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
            $user_maker = $this->actions->getUserMaker();

            $token = $token_resolver->execute($request);
            $response = $request_maker->execute($token, $this->endpoint);
            $attributes = $attributes_resolver->execute($response, $this->path);
            $user = $user_maker->execute($attributes);

            return $user;
        } catch (Throwable $th) {
            throw new AuthenticationException($th->getMessage());
        }
    }
}
