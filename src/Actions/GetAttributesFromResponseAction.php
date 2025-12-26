<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\Actions;

use Illuminate\Support\Arr;

class GetAttributesFromResponseAction
{
    /**
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    public function execute(array $response, string $path): array
    {
        $attributes = empty($path)
            ? $response
            : Arr::get($response, $path, []);

        return $attributes;
    }
}
