<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\Contracts;

interface ResolvesAttributes
{
    /**
     * Extract the user attributes from the validation response payload.
     *
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    public function execute(array $response): array;
}
