<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\Contracts;

interface AccessTokenInterface
{
    /**
     * Return the token as a string.
     */
    public function token(): string;
}
