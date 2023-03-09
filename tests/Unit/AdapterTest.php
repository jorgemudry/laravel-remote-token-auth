<?php

declare(strict_types=1);

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\ActionsResolver;
use JorgeMudry\LaravelRemoteTokenAuth\Adapter;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\AccessTokenInterface;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\AttributesResolverInterface;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\RequestMakerInterface;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\TokenResolverInterface;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\UserMakerInterface;

it('authorizes the user when given a valid token', function (): void {
    $user = new stdClass();
    $responseBody = ['id' => 1, 'name' => 'John Doe'];
    $response = new Response(200, [], json_encode($responseBody));
    Http::fake([$this->endpoint => $response]);

    $tokenResolver = Mockery::mock(TokenResolverInterface::class);
    $tokenResolver->shouldReceive('execute')->once()->andReturn(Mockery::mock(AccessTokenInterface::class));
    $requestMaker = Mockery::mock(RequestMakerInterface::class);
    $requestMaker->shouldReceive('execute')->once()->andReturn($responseBody);
    $attributesResolver = Mockery::mock(AttributesResolverInterface::class);
    $attributesResolver->shouldReceive('execute')->once()->andReturn($responseBody);
    $userMaker = Mockery::mock(UserMakerInterface::class);
    $userMaker->shouldReceive('execute')->once()->andReturn($user);

    $actions = new ActionsResolver($tokenResolver, $requestMaker, $attributesResolver, $userMaker);
    $adapter = new Adapter($this->endpoint, $this->path, $actions);
    $request = Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer valid-token']);
    $result = $adapter->authorize($request);

    expect($result)->toBeInstanceOf(Authenticatable::class);
    expect($result)->toEqual($user);
})->skip();

it('throws an authentication exception when given an invalid token', function (): void {
    $response = new Response(401);
    Http::fake([$this->endpoint => $response]);

    $tokenResolver = Mockery::mock(TokenResolverInterface::class);
    $tokenResolver->shouldReceive('execute')->once()->andThrow(new AuthenticationException());
    $actions = new ActionsResolver($tokenResolver);
    $adapter = new Adapter($this->endpoint, $this->path, $actions);
    $request = Request::create('/', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer invalid-token']);
    $this->expectException(AuthenticationException::class);
    $adapter->authorize($request);
})->skip();

beforeEach(function (): void {
    $this->endpoint = 'http://example.com/validate';
    $this->path = 'user';
});
