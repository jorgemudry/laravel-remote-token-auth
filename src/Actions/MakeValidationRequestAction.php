<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\Actions;

use Illuminate\Support\Facades\Http;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\AccessTokenInterface;

class MakeValidationRequestAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(AccessTokenInterface $token, string $endpoint): array
    {
        $response = Http::asJson()
            ->withToken($token->token())
            ->get($endpoint)
            ->throw()
            ->json();

        return $response;
    }
}
