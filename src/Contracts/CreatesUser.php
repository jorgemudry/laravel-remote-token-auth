<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface CreatesUser
{
    /**
     * Build the authenticated user from the resolved attributes.
     *
     * @param array<string, mixed> $attributes
     */
    public function execute(array $attributes): Authenticatable;
}
