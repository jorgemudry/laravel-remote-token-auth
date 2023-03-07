<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\Actions;

class ActionsResolver
{
    public function getTokenResolver(): GetTokenFromRequestAction
    {
        /** @var GetTokenFromRequestAction $action */
        $action = config('remote-token-auth.actions.token-resolver');

        return new $action();
    }
}
