<?php

declare(strict_types=1);

use JorgeMudry\LaravelRemoteTokenAuth\Actions\CreateUserFromAttributesAction;
use JorgeMudry\LaravelRemoteTokenAuth\ValueObjects\AuthenticatedUser;

it('can create an Authenticatable object from attributes', function (): void {
    $action = new CreateUserFromAttributesAction();
    $attributes = [
        'name' => 'John Doe',
        'email' => 'john.doe@example.com',
        'role' => 'admin',
    ];
    $user = $action->execute($attributes);
    expect($user)->toBeInstanceOf(AuthenticatedUser::class);
    expect($user->name)->toBe($attributes['name']);
    expect($user->email)->toBe($attributes['email']);
    expect($user->role)->toBe($attributes['role']);
});
