<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use JorgeMudry\LaravelRemoteTokenAuth\Tests\Fixtures\CustomUser;

use function Pest\Laravel\getJson;

beforeEach(function (): void {
    config()->set('remote-token-auth.endpoint', 'https://auth.example.com/validate');

    Route::get('/protected', fn (Request $request) => $request->user())->middleware('auth:rta');
});

it('authenticates a request carrying a valid token', function (): void {
    $user = ['id' => 1, 'name' => 'Tony Stark'];
    Http::fake(['auth.example.com/*' => Http::response($user)]);

    getJson('/protected', ['Authorization' => 'Bearer valid-token'])
        ->assertOk()
        ->assertJson($user);

    Http::assertSent(
        fn (ClientRequest $request): bool => $request->url() === 'https://auth.example.com/validate'
            && $request->hasHeader('Authorization', 'Bearer valid-token')
            && $request->hasHeader('Accept', 'application/json')
    );
});

it('rejects a request without a bearer token', function (): void {
    Http::fake();

    getJson('/protected')->assertUnauthorized();

    Http::assertNothingSent();
});

it('rejects the request when the service rejects the token', function (): void {
    Http::fake(['auth.example.com/*' => Http::response(['message' => 'nope'], 401)]);

    getJson('/protected', ['Authorization' => 'Bearer expired-token'])
        ->assertUnauthorized()
        ->assertJson(['message' => 'Invalid access token.']);
});

it('responds with 503 when the validation service fails', function (): void {
    Http::fake(['auth.example.com/*' => Http::response('', 500)]);

    getJson('/protected', ['Authorization' => 'Bearer valid-token'])
        ->assertStatus(503);
});

it('responds with 503 when the endpoint path is wrong', function (): void {
    Http::fake(['auth.example.com/*' => Http::response('', 404)]);

    getJson('/protected', ['Authorization' => 'Bearer valid-token'])
        ->assertStatus(503);
});

it('responds with 503 when the validation service is unreachable', function (): void {
    Http::fake(function (): void {
        throw new ConnectionException('Connection refused');
    });

    getJson('/protected', ['Authorization' => 'Bearer valid-token'])
        ->assertStatus(503);
});

it('extracts the user attributes from the configured response path', function (): void {
    config()->set('remote-token-auth.response.user_path', 'data.user');
    Http::fake([
        'auth.example.com/*' => Http::response(['data' => ['user' => ['id' => 7, 'name' => 'Peter Parker']]]),
    ]);

    getJson('/protected', ['Authorization' => 'Bearer valid-token'])
        ->assertOk()
        ->assertJson(['id' => 7, 'name' => 'Peter Parker']);
});

it('rejects the request when the response contains no user attributes', function (): void {
    config()->set('remote-token-auth.response.user_path', 'data');
    Http::fake(['auth.example.com/*' => Http::response(['unexpected' => 'shape'])]);

    getJson('/protected', ['Authorization' => 'Bearer valid-token'])
        ->assertUnauthorized();
});

it('rejects the request when the user attributes have no identifier', function (): void {
    Http::fake(['auth.example.com/*' => Http::response(['name' => 'No Id'])]);

    getJson('/protected', ['Authorization' => 'Bearer valid-token'])
        ->assertUnauthorized();
});

it('uses the configured user class', function (): void {
    config()->set('remote-token-auth.response.user_class', CustomUser::class);
    Http::fake(['auth.example.com/*' => Http::response(['id' => 1])]);

    Route::get('/whoami', fn (Request $request): array => ['class' => get_class($request->user())])
        ->middleware('auth:rta');

    getJson('/whoami', ['Authorization' => 'Bearer valid-token'])
        ->assertOk()
        ->assertJson(['class' => CustomUser::class]);
});

it('fails with a server error when the endpoint is not configured', function (): void {
    config()->set('remote-token-auth.endpoint', '');
    Http::fake();

    getJson('/protected', ['Authorization' => 'Bearer valid-token'])
        ->assertStatus(500);

    Http::assertNothingSent();
});

it('caches successful validations when caching is enabled', function (): void {
    config()->set('remote-token-auth.cache.enabled', true);
    config()->set('remote-token-auth.cache.store', 'array');
    Http::fake(['auth.example.com/*' => Http::response(['id' => 1])]);

    getJson('/protected', ['Authorization' => 'Bearer valid-token'])->assertOk();
    app('auth')->forgetGuards();
    getJson('/protected', ['Authorization' => 'Bearer valid-token'])->assertOk();

    Http::assertSentCount(1);
});

it('still authenticates when the configured cache store does not exist', function (): void {
    config()->set('remote-token-auth.cache.enabled', true);
    config()->set('remote-token-auth.cache.store', 'not-a-real-store');
    Http::fake(['auth.example.com/*' => Http::response(['id' => 1])]);

    getJson('/protected', ['Authorization' => 'Bearer valid-token'])->assertOk();
});

it('honors the legacy request-maker config key from 0.x published configs', function (): void {
    config()->set('remote-token-auth.actions.token-validator', null);
    config()->set(
        'remote-token-auth.actions.request-maker',
        JorgeMudry\LaravelRemoteTokenAuth\Actions\MakeValidationRequestAction::class,
    );
    Http::fake(['auth.example.com/*' => Http::response(['id' => 1])]);

    getJson('/protected', ['Authorization' => 'Bearer valid-token'])->assertOk();
});

it('validates remotely on every request when caching is disabled', function (): void {
    Http::fake(['auth.example.com/*' => Http::response(['id' => 1])]);

    getJson('/protected', ['Authorization' => 'Bearer valid-token'])->assertOk();
    app('auth')->forgetGuards();
    getJson('/protected', ['Authorization' => 'Bearer valid-token'])->assertOk();

    Http::assertSentCount(2);
});
