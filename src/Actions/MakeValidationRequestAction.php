<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\Actions;

use Illuminate\Support\Facades\Http;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\AccessTokenInterface;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\ValidatesToken;
use JorgeMudry\LaravelRemoteTokenAuth\Exceptions\InvalidUserResponseException;

class MakeValidationRequestAction implements ValidatesToken
{
    public function __construct(
        protected string $endpoint,
        protected float $timeout = 5.0,
        protected float $connect_timeout = 2.0,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(AccessTokenInterface $token): array
    {
        // The timeouts are passed as raw Guzzle options because they may be
        // fractional and PendingRequest::timeout() is int-typed on Laravel 10.
        $response = Http::acceptJson()
            ->withOptions([
                'timeout' => $this->timeout,
                'connect_timeout' => $this->connect_timeout,
            ])
            ->withToken($token->token())
            ->get($this->endpoint)
            ->throw()
            ->json();

        if ( ! is_array($response)) {
            throw new InvalidUserResponseException('The token validation service did not return a JSON object.');
        }

        return $response;
    }
}
