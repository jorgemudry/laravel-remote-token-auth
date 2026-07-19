<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JorgeMudry\LaravelRemoteTokenAuth\ValueObjects\AuthenticatedUser;

it('implements the Authenticatable interface', function (): void {
    $user = new AuthenticatedUser(['id' => 1, 'name' => 'Tony']);

    expect(class_implements($user))->toContain(Authenticatable::class);
});

it('implements the Arrayable interface', function (): void {
    $user = new AuthenticatedUser(['id' => 1, 'name' => 'Tony']);

    expect(class_implements($user))->toContain(Arrayable::class);
});

it('implements the ArrayAccess interface', function (): void {
    $user = new AuthenticatedUser(['id' => 1, 'name' => 'Tony']);

    expect(class_implements($user))->toContain(ArrayAccess::class);
});

it('implements the Jsonable interface', function (): void {
    $user = new AuthenticatedUser(['id' => 1, 'name' => 'Tony']);

    expect(class_implements($user))->toContain(Jsonable::class);
});

it('implements the JsonSerializable interface', function (): void {
    $user = new AuthenticatedUser(['id' => 1, 'name' => 'Tony']);

    expect(class_implements($user))->toContain(JsonSerializable::class);
});

it('reports existing attributes even when their value is null', function (): void {
    $user = new AuthenticatedUser(['id' => 1, 'nickname' => null]);

    expect(isset($user['nickname']))->toBeTrue();
    expect(isset($user['missing']))->toBeFalse();
});

it('keeps isset and read access consistent for keys with surrounding whitespace', function (): void {
    $user = new AuthenticatedUser(['id' => 1, 'name' => 'Tony']);

    expect(isset($user['name ']))->toBeTrue();
    expect($user['name '])->toBe('Tony');
});

it('can access all elements from the attributes as properties', function (): void {
    $user = new AuthenticatedUser([
        'id' => 1,
        'first_name' => 'Tony',
        'last_name' => 'Stark',
        'name' => 'IronMan',
    ]);

    expect($user->id)->toEqual(1);
    expect($user->first_name)->toEqual('Tony');
    expect($user->last_name)->toEqual('Stark');
    expect($user->name)->toEqual('IronMan');
});
