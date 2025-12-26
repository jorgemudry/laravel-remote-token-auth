<?php

declare(strict_types=1);

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\MakeValidationRequestAction;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\AccessTokenInterface;

test('execute method should return an array', function (): void {
    Http::fake([
        'http://example.com/api' => Http::response(['response' => 'success'], 200),
    ]);

    $token = Mockery::mock(AccessTokenInterface::class);
    $token->shouldReceive('token')->once()->andReturn('valid_token');

    $action = new MakeValidationRequestAction();
    $result = $action->execute($token, 'http://example.com/api');

    expect($result)->toBeArray();
    expect($result)->toBe(['response' => 'success']);
});

test('execute method should throw an exception if the HTTP response is not successful', function (): void {
    Http::fake([
        'http://example.com/api' => Http::response(['error' => 'Unauthorized'], 401),
    ]);

    $token = Mockery::mock(AccessTokenInterface::class);
    $token->shouldReceive('token')->once()->andReturn('valid_token');

    $action = new MakeValidationRequestAction();

    expect(fn () => $action->execute($token, 'http://example.com/api'))
        ->toThrow(RequestException::class);
});
