<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\MakeValidationRequestAction;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\AccessTokenInterface;

test('execute method should return an array', function (): void {
    /** @var \Mockery\MockInterface|\Mockery\LegacyMockInterface $token */
    $token = Mockery::mock(AccessTokenInterface::class);
    $token->shouldReceive('token')->once()->andReturn('valid_token');

    $httpMock = Mockery::mock('alias:' . Http::class);
    $httpMock->shouldReceive('asJson')->once()->andReturnSelf();
    $httpMock->shouldReceive('withToken')->once()->with('valid_token')->andReturnSelf();
    $httpMock->shouldReceive('get')->once()->with('http://example.com/api')->andReturnSelf();
    $httpMock->shouldReceive('throw')->once()->andReturnSelf();
    $httpMock->shouldReceive('json')->once()->andReturn(['response' => 'success']);

    $action = new MakeValidationRequestAction();

    $result = $action->execute($token, 'http://example.com/api');

    expect($result)->toBeArray();
});

test('execute method should throw an exception if the HTTP response is not successful', function (): void {
    $token = Mockery::mock(AccessTokenInterface::class);
    $token->shouldReceive('token')->once()->andReturn('valid_token');

    $httpMock = Mockery::mock('alias:' . Http::class);
    $httpMock->shouldReceive('asJson')->once()->andReturnSelf();
    $httpMock->shouldReceive('withToken')->once()->with('valid_token')->andReturnSelf();
    $httpMock->shouldReceive('get')->once()->with('http://example.com/api')->andReturnSelf();
    $httpMock->shouldReceive('throw')->once()->andThrow(new Exception('Invalid response'));

    $action = new MakeValidationRequestAction();

    expect(function () use ($action, $token): void {
        $action->execute($token, 'http://example.com/api');
    })->toThrow(Exception::class, 'Invalid response');
});
