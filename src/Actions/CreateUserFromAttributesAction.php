<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use JorgeMudry\LaravelRemoteTokenAuth\ValueObjects\AuthenticatedUser;

class CreateUserFromAttributesAction
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function execute(array $attributes): Authenticatable
    {
        return new AuthenticatedUser($attributes);
    }
}
