<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\Actions;

use Illuminate\Support\Arr;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\ResolvesAttributes;
use JorgeMudry\LaravelRemoteTokenAuth\Exceptions\InvalidUserResponseException;

class GetAttributesFromResponseAction implements ResolvesAttributes
{
    public function __construct(protected string $path = '')
    {
    }

    /**
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    public function execute(array $response): array
    {
        $attributes = $this->path === ''
            ? $response
            : Arr::get($response, $this->path, []);

        if ( ! is_array($attributes) || $attributes === []) {
            throw new InvalidUserResponseException(sprintf(
                'The validation response does not contain user attributes at path "%s".',
                $this->path,
            ));
        }

        /** @var array<string, mixed> $attributes */
        return $attributes;
    }
}
