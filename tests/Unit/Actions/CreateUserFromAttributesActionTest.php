<?php

declare(strict_types=1);

use JorgeMudry\LaravelRemoteTokenAuth\Actions\CreateUserFromAttributesAction;
use JorgeMudry\LaravelRemoteTokenAuth\Exceptions\InvalidUserResponseException;
use JorgeMudry\LaravelRemoteTokenAuth\Exceptions\MissingConfigurationException;
use JorgeMudry\LaravelRemoteTokenAuth\Tests\Fixtures\CustomUser;
use JorgeMudry\LaravelRemoteTokenAuth\ValueObjects\AuthenticatedUser;

it('can create an Authenticatable object from attributes', function (): void {
    $attributes = [
        'id' => 1,
        'name' => 'John Doe',
        'email' => 'john.doe@example.com',
        'role' => 'admin',
    ];

    $user = (new CreateUserFromAttributesAction())->execute($attributes);

    expect($user)->toBeInstanceOf(AuthenticatedUser::class);
    expect($user->name)->toBe($attributes['name']);
    expect($user->email)->toBe($attributes['email']);
    expect($user->role)->toBe($attributes['role']);
});

it('creates an instance of the configured user class', function (): void {
    $user = (new CreateUserFromAttributesAction(CustomUser::class))->execute(['id' => 1]);

    expect($user)->toBeInstanceOf(CustomUser::class);
});

it('rejects a user class that is not authenticatable', function (): void {
    new CreateUserFromAttributesAction(stdClass::class);
})->throws(MissingConfigurationException::class);

it('throws when the attributes are empty', function (): void {
    (new CreateUserFromAttributesAction())->execute([]);
})->throws(InvalidUserResponseException::class);

it('throws when the attributes are missing the auth identifier', function (): void {
    (new CreateUserFromAttributesAction())->execute(['name' => 'No Id']);
})->throws(InvalidUserResponseException::class);
