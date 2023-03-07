<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\ValueObjects;

use InvalidArgumentException;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\AccessTokenInterface;

class AccessToken implements AccessTokenInterface
{
    public function __construct(protected string $token)
    {
        $this->validate();
    }

    public function token(): string
    {
        return $this->token;
    }

    protected function validate(): void
    {
        $this->token = trim(strval(preg_replace('/[^[:print:]]/', '', $this->token)));

        if (empty($this->token)) {
            throw new InvalidArgumentException('A bearer token is required.');
        }
    }
}
