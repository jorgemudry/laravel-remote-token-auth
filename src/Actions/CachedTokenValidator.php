<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\Actions;

use Illuminate\Support\Facades\Cache;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\AccessTokenInterface;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\CreatesUser;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\ResolvesAttributes;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\ValidatesToken;
use Throwable;

/**
 * Decorates any token validator with a cache layer, so repeated requests with
 * the same token skip the round-trip to the validation service.
 *
 * Two invariants hold: only responses that the whole pipeline accepts
 * (attributes resolved, user built) are ever cached, so a malformed
 * "successful" response is never pinned for the TTL; and a failing or
 * misconfigured cache store falls back to direct validation instead of
 * rejecting valid tokens (the cache error is report()ed).
 *
 * A revoked token stays valid until the TTL expires.
 */
class CachedTokenValidator implements ValidatesToken
{
    public function __construct(
        protected ValidatesToken $validator,
        protected ResolvesAttributes $attributes_resolver,
        protected CreatesUser $user_maker,
        protected ?string $store,
        protected int $ttl,
        protected string $prefix,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(AccessTokenInterface $token): array
    {
        $key = $this->prefix . ':' . hash('sha256', $token->token());

        try {
            $cached = Cache::store($this->store)->get($key);

            if (is_array($cached)) {
                /** @var array<string, mixed> $cached */
                return $cached;
            }
        } catch (Throwable $exception) {
            report($exception);

            return $this->validator->execute($token);
        }

        $response = $this->validator->execute($token);

        // Run the rest of the pipeline before caching; if this throws, the
        // response is rejected upstream and must not be stored.
        $this->user_maker->execute($this->attributes_resolver->execute($response));

        try {
            Cache::store($this->store)->put($key, $response, $this->ttl);
        } catch (Throwable $exception) {
            report($exception);
        }

        return $response;
    }
}
