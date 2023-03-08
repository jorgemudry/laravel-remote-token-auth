<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\Actions;

use Illuminate\Support\Arr;

class GetAttributesFromResponseAction
{
    /**
     * @param array<string, mixed> $respose
     * @return array<string, mixed>
     */
    public function execute(array $respose, string $path): array
    {
        $attributes = empty($path)
            ? $respose
            : Arr::get($respose, $path, []);

        return $attributes;
    }
}
