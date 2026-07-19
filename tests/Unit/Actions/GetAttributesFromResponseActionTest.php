<?php

declare(strict_types=1);

use JorgeMudry\LaravelRemoteTokenAuth\Actions\GetAttributesFromResponseAction;
use JorgeMudry\LaravelRemoteTokenAuth\Exceptions\InvalidUserResponseException;

it('can get attributes from a response without a path', function (): void {
    $response = [
        'id' => 123,
        'name' => 'John Doe',
        'email' => 'john.doe@example.com',
        'role' => 'admin',
    ];

    $attributes = (new GetAttributesFromResponseAction())->execute($response);

    expect($attributes)->toBe($response);
});

it('can get attributes from a response with a path', function (): void {
    $response = [
        'data' => [
            'id' => 123,
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'role' => 'admin',
        ],
    ];

    $attributes = (new GetAttributesFromResponseAction('data'))->execute($response);

    expect($attributes)->toBe($response['data']);
});

it('supports nested dot-notation paths', function (): void {
    $response = ['data' => ['user' => ['id' => 7]]];

    $attributes = (new GetAttributesFromResponseAction('data.user'))->execute($response);

    expect($attributes)->toBe(['id' => 7]);
});

it('throws when the path does not exist in the response', function (): void {
    $response = ['data' => ['id' => 123]];

    (new GetAttributesFromResponseAction('missing.path'))->execute($response);
})->throws(InvalidUserResponseException::class);

it('throws when the path points to a non-array value', function (): void {
    $response = ['data' => 'not-an-array'];

    (new GetAttributesFromResponseAction('data'))->execute($response);
})->throws(InvalidUserResponseException::class);

it('throws when the response is empty', function (): void {
    (new GetAttributesFromResponseAction())->execute([]);
})->throws(InvalidUserResponseException::class);
