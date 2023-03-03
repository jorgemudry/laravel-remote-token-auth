<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use JorgeMudry\LaravelRemoteTokenAuth\LaravelRemoteTokenAuthAdapter;
use JorgeMudry\LaravelRemoteTokenAuth\ValueObjects\AuthenticatedUser;

beforeEach(function () {
    $this->request = $this->getMockBuilder(Request::class)
        ->disableOriginalConstructor()
        ->getMock();

    $this->endpoint = 'https://my-api.com/token';
    $this->path = '';
    $this->user_class = '';

    $this->adapter = new LaravelRemoteTokenAuthAdapter($this->endpoint, $this->path, $this->user_class);
});

it('returns an authenticated user', function () {
    Http::fake(function (ClientRequest $request) {
        return Http::response(json_encode(['id' => 1, 'name' => 'Tony']), 200);
    });

    $this->request->expects($this->once())
        ->method('bearerToken')
        ->willReturn('asdasdasdxczxcsersdef');

    $user = $this->adapter->authorize($this->request);

    expect($user)->toBeInstanceOf(AuthenticatedUser::class);
});

it('throws an AuthenticationException when no token is provided', function () {
    Http::fake(function (ClientRequest $request) {
        return Http::response(json_encode(['id' => 1, 'name' => 'Tony']), 200);
    });

    $this->request->expects($this->once())
        ->method('bearerToken')
        ->willReturn('');

    $user = $this->adapter->authorize($this->request);
})->throws(
    AuthenticationException::class,
    'A bearer token is required.'
);

it('throws an AuthenticationException on invalid response', function () {
    Http::fake(function (ClientRequest $request) {
        return Http::response(status: 401);
    });

    $this->request->expects($this->once())
        ->method('bearerToken')
        ->willReturn('some-token');

    $user = $this->adapter->authorize($this->request);
})->throws(
    AuthenticationException::class,
    'HTTP request returned status code 401'
);

it('sends the token as a bearer token in the request', function () {
    Http::fake(function (ClientRequest $request) {
        return Http::response(json_encode(['id' => 1, 'name' => 'Tony']), 200);
    });

    $this->request->expects($this->once())
        ->method('bearerToken')
        ->willReturn('this-is-a-token');

    $user = $this->adapter->authorize($this->request);

    Http::assertSent(function (ClientRequest $request) {
        return $request->hasHeader('Authorization', 'Bearer this-is-a-token') &&
               $request->url() == 'https://my-api.com/token';
    });
});

it('returns an AuthenticatedUser when the custom class does not implements Authenticatable', function () {
    Http::fake(function (ClientRequest $request) {
        return Http::response(json_encode(['id' => 1, 'name' => 'Tony']), 200);
    });

    $this->request->expects($this->once())
        ->method('bearerToken')
        ->willReturn('this-is-a-token');

    $adapter = new LaravelRemoteTokenAuthAdapter($this->endpoint, $this->path, InvalidClass::class);
    $user = $adapter->authorize($this->request);

    expect($user)->toBeInstanceOf(AuthenticatedUser::class);
});

it('returns a cutom User class when is valid', function () {
    Http::fake(function (ClientRequest $request) {
        return Http::response(json_encode(['id' => 1, 'name' => 'Tony']), 200);
    });

    $this->request->expects($this->once())
        ->method('bearerToken')
        ->willReturn('this-is-a-token');

    $adapter = new LaravelRemoteTokenAuthAdapter($this->endpoint, $this->path, ValidClass::class);
    $user = $adapter->authorize($this->request);

    expect($user)->toBeInstanceOf(ValidClass::class);
});

it('can specify the user data path in the response', function () {
    Http::fake(function (ClientRequest $request) {
        return Http::response(
            json_encode([
                'data' => [
                    'id' => 1,
                    'name' => 'Tony',
                ],
            ]),
            200
        );
    });
    $this->request->expects($this->once())
        ->method('bearerToken')
        ->willReturn('this-is-a-token');

    $adapter = new LaravelRemoteTokenAuthAdapter($this->endpoint, 'data', '');
    /** @var AuthenticatedUser $user */
    $user = $adapter->authorize($this->request);

    expect($user)->toBeInstanceOf(AuthenticatedUser::class);
    expect($user->toArray())->toMatchArray([
        'id' => 1,
        'name' => 'Tony',
    ]);
});

class InvalidClass //phpcs:ignore
{
}

class ValidClass extends GenericUser //phpcs:ignore
{
}
