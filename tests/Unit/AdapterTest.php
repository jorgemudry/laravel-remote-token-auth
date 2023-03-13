<?php

declare(strict_types=1);

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\ActionsResolver;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\MakeValidationRequestAction;
use JorgeMudry\LaravelRemoteTokenAuth\Adapter;
use Mockery\MockInterface;

it('authorizes the user when given a valid token', function (): void {
    $this->instance(
        MakeValidationRequestAction::class,
        Mockery::mock(MakeValidationRequestAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('execute')->once()->andReturn(['id' => 1, 'name' => 'Tony Stark']);
        })
    );

    $adapter = new Adapter($this->endpoint, $this->path, new ActionsResolver());
    $request = createRequest(
        method: 'GET',
        uri: '/',
        server: ['HTTP_AUTHORIZATION' => 'Bearer valid-token']
    );
    $user = $adapter->authorize($request);

    expect($user)->toBeInstanceOf(Authenticatable::class);
    expect($user->id)->toEqual(1);
    expect($user->name)->toEqual('Tony Stark');
});

it('throws an authentication exception when given an invalid token', function (): void {
    $this->instance(
        MakeValidationRequestAction::class,
        Mockery::mock(MakeValidationRequestAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('execute')->once()->andThrow(new \Exception('User not valid.'));
        })
    );

    $adapter = new Adapter($this->endpoint, $this->path, new ActionsResolver());
    $request = createRequest(
        method: 'GET',
        uri: '/',
        server: ['HTTP_AUTHORIZATION' => 'Bearer some token']
    );
    $adapter->authorize($request);
})->throws(AuthenticationException::class);

beforeEach(function (): void {
    $this->endpoint = 'http://example.com/validate';
    $this->path = '';
});
