<?php

declare(strict_types=1);

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\CreateUserFromAttributesAction;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\GetAttributesFromResponseAction;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\GetTokenFromRequestAction;
use JorgeMudry\LaravelRemoteTokenAuth\Adapter;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\AccessTokenInterface;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\ValidatesToken;
use JorgeMudry\LaravelRemoteTokenAuth\Exceptions\ValidationServiceUnavailableException;
use JorgeMudry\LaravelRemoteTokenAuth\ValueObjects\AuthenticatedUser;

function adapter_with_validation(Closure $validation): Adapter
{
    $validator = new class ($validation) implements ValidatesToken {
        public function __construct(private Closure $validation)
        {
        }

        public function execute(AccessTokenInterface $token): array
        {
            return ($this->validation)($token);
        }
    };

    return new Adapter(
        new GetTokenFromRequestAction(),
        $validator,
        new GetAttributesFromResponseAction(),
        new CreateUserFromAttributesAction(),
        'rta',
    );
}

function authenticated_request(): Request
{
    return createRequest(
        method: 'GET',
        uri: '/',
        server: ['HTTP_AUTHORIZATION' => 'Bearer valid-token'],
    );
}

it('authorizes the user when given a valid token', function (): void {
    $adapter = adapter_with_validation(fn (): array => ['id' => 1, 'name' => 'Tony Stark']);

    $user = $adapter->authorize(authenticated_request());

    expect($user)->toBeInstanceOf(AuthenticatedUser::class);
    expect($user->getAuthIdentifier())->toEqual(1);
    expect($user->name)->toEqual('Tony Stark');
});

it('throws an authentication exception when the request has no token', function (): void {
    $adapter = adapter_with_validation(fn (): array => ['id' => 1]);

    $adapter->authorize(createRequest(method: 'GET', uri: '/'));
})->throws(AuthenticationException::class, 'Invalid access token.');

it('throws a service unavailable exception when the service is unreachable', function (): void {
    $adapter = adapter_with_validation(function (): array {
        throw new ConnectionException('Connection refused');
    });

    $adapter->authorize(authenticated_request());
})->throws(ValidationServiceUnavailableException::class);

it('throws a service unavailable exception when the service returns a server error', function (): void {
    $adapter = adapter_with_validation(function (): array {
        throw new RequestException(new Response(new Psr7Response(503)));
    });

    $adapter->authorize(authenticated_request());
})->throws(ValidationServiceUnavailableException::class);

it('throws an authentication exception when the service rejects the token', function (int $status): void {
    $adapter = adapter_with_validation(function () use ($status): array {
        throw new RequestException(new Response(new Psr7Response($status)));
    });

    $adapter->authorize(authenticated_request());
})->with([400, 401, 403])->throws(AuthenticationException::class, 'Invalid access token.');

it('treats unexpected client errors as a service problem, not a token rejection', function (int $status): void {
    $adapter = adapter_with_validation(function () use ($status): array {
        throw new RequestException(new Response(new Psr7Response($status)));
    });

    $adapter->authorize(authenticated_request());
})->with([404, 405, 429])->throws(ValidationServiceUnavailableException::class);

it('throws a service unavailable exception when a raw Guzzle client cannot connect', function (): void {
    $adapter = adapter_with_validation(function (): array {
        throw new ConnectException('Connection refused', new Psr7Request('GET', 'https://auth.example.com'));
    });

    $adapter->authorize(authenticated_request());
})->throws(ValidationServiceUnavailableException::class);

it('throws an authentication exception when the response has no user attributes', function (): void {
    $adapter = adapter_with_validation(fn (): array => []);

    $adapter->authorize(authenticated_request());
})->throws(AuthenticationException::class, 'Invalid access token.');

it('hides internal error details from the authentication exception', function (): void {
    $adapter = adapter_with_validation(function (): array {
        throw new RuntimeException('secret internal detail: https://internal.example.com');
    });

    try {
        $adapter->authorize(authenticated_request());
        $this->fail('An AuthenticationException was expected.');
    } catch (AuthenticationException $exception) {
        expect($exception->getMessage())->toBe('Invalid access token.');
        expect($exception->guards())->toBe(['rta']);
    }
});
