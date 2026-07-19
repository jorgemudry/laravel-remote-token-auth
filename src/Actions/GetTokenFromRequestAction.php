<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\Actions;

use Illuminate\Http\Request;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\AccessTokenInterface;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\ResolvesToken;
use JorgeMudry\LaravelRemoteTokenAuth\ValueObjects\AccessToken;

class GetTokenFromRequestAction implements ResolvesToken
{
    public function execute(Request $request): AccessTokenInterface
    {
        return new AccessToken($request->bearerToken() ?? '');
    }
}
