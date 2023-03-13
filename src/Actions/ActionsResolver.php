<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\Actions;

class ActionsResolver
{
    public function getTokenResolver(): GetTokenFromRequestAction
    {
        /** @var GetTokenFromRequestAction $action */
        $action = $this->resolve('token-resolver');

        return $action;
    }

    public function getRequestMaker(): MakeValidationRequestAction
    {
        /** @var MakeValidationRequestAction $action */
        $action = $this->resolve('request-maker');

        return $action;
    }

    public function getAttributesResolver(): GetAttributesFromResponseAction
    {
        /** @var GetAttributesFromResponseAction $action */
        $action = $this->resolve('attributes-resolver');

        return $action;
    }

    public function getUserMaker(): CreateUserFromAttributesAction
    {
        /** @var CreateUserFromAttributesAction $action */
        $action = $this->resolve('user-maker');

        return $action;
    }

    protected function resolve(string $config): object
    {
        return app(config('remote-token-auth.actions.' . $config));
    }
}
