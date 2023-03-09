<?php

declare(strict_types=1);

use JorgeMudry\LaravelRemoteTokenAuth\Contracts\AccessTokenInterface;
use JorgeMudry\LaravelRemoteTokenAuth\ValueObjects\AccessToken;

it('can create an access token instance', function (): void {
    $token = new AccessToken('valid_token');
    expect($token)->toBeInstanceOf(AccessTokenInterface::class);
});

it('can get the token value', function (): void {
    $token = new AccessToken('valid_token');
    expect($token->token())->toBe('valid_token');
});

it('trims and removes non-printable characters from the token', function (): void {
    $token = new AccessToken(' valid_ token  ');
    expect($token->token())->toBe('valid_ token');
});

it('throws an exception if token is empty', function (): void {
    expect(function (): void {
        new AccessToken('');
    })->toThrow(InvalidArgumentException::class, 'A bearer token is required.');
});

it('throws an exception if token contains only non-printable characters', function (): void {
    expect(function (): void {
        new AccessToken("\n\t");
    })->toThrow(InvalidArgumentException::class, 'A bearer token is required.');
});
