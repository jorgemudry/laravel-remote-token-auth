<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\CachedTokenValidator;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\CreateUserFromAttributesAction;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\GetAttributesFromResponseAction;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\AccessTokenInterface;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\ValidatesToken;
use JorgeMudry\LaravelRemoteTokenAuth\Exceptions\InvalidUserResponseException;
use JorgeMudry\LaravelRemoteTokenAuth\ValueObjects\AccessToken;

function counting_validator(array $response = ['id' => 1]): ValidatesToken
{
    return new class ($response) implements ValidatesToken {
        public int $calls = 0;

        public function __construct(private array $response)
        {
        }

        public function execute(AccessTokenInterface $token): array
        {
            $this->calls++;

            return $this->response;
        }
    };
}

function cached_validator(ValidatesToken $inner, string $store = 'array'): CachedTokenValidator
{
    return new CachedTokenValidator(
        $inner,
        new GetAttributesFromResponseAction(),
        new CreateUserFromAttributesAction(),
        $store,
        60,
        'remote-token-auth',
    );
}

it('only calls the inner validator once for the same token', function (): void {
    $inner = counting_validator();
    $validator = cached_validator($inner);
    $token = new AccessToken('some-token');

    expect($validator->execute($token))->toBe(['id' => 1]);
    expect($validator->execute($token))->toBe(['id' => 1]);
    expect($inner->calls)->toBe(1);
});

it('validates different tokens independently', function (): void {
    $inner = counting_validator();
    $validator = cached_validator($inner);

    $validator->execute(new AccessToken('token-one'));
    $validator->execute(new AccessToken('token-two'));

    expect($inner->calls)->toBe(2);
});

it('does not cache failed validations', function (): void {
    $inner = new class () implements ValidatesToken {
        public int $calls = 0;

        public function execute(AccessTokenInterface $token): array
        {
            $this->calls++;

            throw new RuntimeException('Validation failed.');
        }
    };
    $validator = cached_validator($inner);
    $token = new AccessToken('some-token');

    expect(fn (): array => $validator->execute($token))->toThrow(RuntimeException::class);
    expect(fn (): array => $validator->execute($token))->toThrow(RuntimeException::class);
    expect($inner->calls)->toBe(2);
});

it('does not cache responses that the rest of the pipeline rejects', function (): void {
    $inner = counting_validator(['name' => 'No Identifier']);
    $validator = cached_validator($inner);
    $token = new AccessToken('some-token');

    expect(fn (): array => $validator->execute($token))->toThrow(InvalidUserResponseException::class);
    expect(fn (): array => $validator->execute($token))->toThrow(InvalidUserResponseException::class);
    expect($inner->calls)->toBe(2);
    expect(Cache::store('array')->get('remote-token-auth:' . hash('sha256', 'some-token')))->toBeNull();
});

it('ignores a cached value that is not an array', function (): void {
    $key = 'remote-token-auth:' . hash('sha256', 'some-token');
    Cache::store('array')->put($key, 'garbage', 60);

    $inner = counting_validator();
    $validator = cached_validator($inner);

    expect($validator->execute(new AccessToken('some-token')))->toBe(['id' => 1]);
    expect($inner->calls)->toBe(1);
    expect(Cache::store('array')->get($key))->toBe(['id' => 1]);
});

it('falls back to direct validation when the cache store is unusable', function (): void {
    $inner = counting_validator();
    $validator = cached_validator($inner, 'not-a-real-store');
    $token = new AccessToken('some-token');

    expect($validator->execute($token))->toBe(['id' => 1]);
    expect($validator->execute($token))->toBe(['id' => 1]);
    expect($inner->calls)->toBe(2);
});
