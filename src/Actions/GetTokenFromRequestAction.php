<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\Actions;

use Illuminate\Http\Request;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\AccessTokenInterface;
use JorgeMudry\LaravelRemoteTokenAuth\ValueObjects\AccessToken;

class GetTokenFromRequestAction
{
    public function execute(Request $request): AccessTokenInterface
    {
        return new AccessToken($request->bearerToken() ?? '');
    }
}
