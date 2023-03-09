<?php

declare(strict_types=1);

use JorgeMudry\LaravelRemoteTokenAuth\Actions\GetAttributesFromResponseAction;

it('can get attributes from a response without a path', function (): void {
    $action = new GetAttributesFromResponseAction();
    $response = [
        'id' => 123,
        'name' => 'John Doe',
        'email' => 'john.doe@example.com',
        'role' => 'admin',
    ];
    $attributes = $action->execute($response, '');
    expect($attributes)->toBe($response);
});

it('can get attributes from a response with a path', function (): void {
    $action = new GetAttributesFromResponseAction();
    $response = [
        'data' => [
            'id' => 123,
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'role' => 'admin',
        ]
    ];
    $attributes = $action->execute($response, 'data');
    expect($attributes)->toBe($response['data']);
});

it('returns an empty array if the path does not exist in the response', function (): void {
    $action = new GetAttributesFromResponseAction();
    $response = [
        'data' => [
            'id' => 123,
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'role' => 'admin',
        ]
    ];
    $attributes = $action->execute($response, 'missing.path');
    expect($attributes)->toBe([]);
});
