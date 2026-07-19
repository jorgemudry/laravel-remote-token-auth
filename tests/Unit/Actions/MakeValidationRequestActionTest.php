<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\MakeValidationRequestAction;
use JorgeMudry\LaravelRemoteTokenAuth\Exceptions\InvalidUserResponseException;
use JorgeMudry\LaravelRemoteTokenAuth\ValueObjects\AccessToken;

it('sends the token to the endpoint and returns the decoded response', function (): void {
    Http::fake(['auth.example.com/*' => Http::response(['id' => 1, 'name' => 'Tony Stark'])]);

    $action = new MakeValidationRequestAction('https://auth.example.com/validate');
    $response = $action->execute(new AccessToken('secret-token'));

    expect($response)->toBe(['id' => 1, 'name' => 'Tony Stark']);
    Http::assertSent(
        fn (ClientRequest $request): bool => $request->hasHeader('Authorization', 'Bearer secret-token')
            && $request->hasHeader('Accept', 'application/json')
    );
});

it('throws a request exception when the service rejects the token', function (): void {
    Http::fake(['auth.example.com/*' => Http::response('', 401)]);

    $action = new MakeValidationRequestAction('https://auth.example.com/validate');

    expect(fn (): array => $action->execute(new AccessToken('bad-token')))
        ->toThrow(RequestException::class);
});

it('throws a request exception when the service fails', function (): void {
    Http::fake(['auth.example.com/*' => Http::response('', 500)]);

    $action = new MakeValidationRequestAction('https://auth.example.com/validate');

    expect(fn (): array => $action->execute(new AccessToken('secret-token')))
        ->toThrow(RequestException::class);
});

it('throws when the response is not a JSON object', function (): void {
    Http::fake([
        'auth.example.com/*' => Http::response('"just-a-string"', 200, ['Content-Type' => 'application/json']),
    ]);

    $action = new MakeValidationRequestAction('https://auth.example.com/validate');

    expect(fn (): array => $action->execute(new AccessToken('secret-token')))
        ->toThrow(InvalidUserResponseException::class);
});
