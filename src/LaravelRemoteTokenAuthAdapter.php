<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\AdapterInterface;
use JorgeMudry\LaravelRemoteTokenAuth\ValueObjects\AuthenticatedUser;
use Throwable;

class LaravelRemoteTokenAuthAdapter implements AdapterInterface
{
    public function __construct(
        protected string $endpoint,
        protected string $path,
    ) {
    }

    public function authorize(Request $request): Authenticatable
    {
        try {
            $token = $request->bearerToken() ?? '';

            if (empty($token)) {
                throw new Exception('A bearer token is required.');
            }

            $response = Http::asJson()
                ->withToken($token)
                ->get($this->endpoint)
                ->throw()
                ->json();
        } catch (Throwable $th) {
            throw new AuthenticationException($th->getMessage());
        }

        $attributes = Arr::get($response, $this->path, []);

        return new AuthenticatedUser($attributes);
    }
}
