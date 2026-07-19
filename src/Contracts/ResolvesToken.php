<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\Contracts;

use Illuminate\Http\Request;

interface ResolvesToken
{
    /**
     * Extract the access token from the incoming request.
     */
    public function execute(Request $request): AccessTokenInterface;
}
