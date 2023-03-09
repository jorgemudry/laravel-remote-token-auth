<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\GetTokenFromRequestAction;

it('can get the access token from a request', function (): void {
    $action = new GetTokenFromRequestAction();
    $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9';
    $request = Request::create('/', 'GET');
    $request->headers->set('Authorization', "Bearer {$token}");
    $accessToken = $action->execute($request);
    expect($accessToken->token())->toBe($token);
});

it('throws an exception if no access token is provided', function (): void {
    $action = new GetTokenFromRequestAction();
    $request = Request::create('/', 'GET');
    $action->execute($request);
})->throws(\InvalidArgumentException::class, 'A bearer token is required.');
