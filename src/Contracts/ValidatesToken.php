<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\Contracts;

interface ValidatesToken
{
    /**
     * Validate the token against the external service and return the raw
     * response payload.
     *
     * Error signaling for custom implementations: throw
     * JorgeMudry\LaravelRemoteTokenAuth\Exceptions\ValidationServiceUnavailableException
     * when the service is unreachable or failing (renders as 503, so clients
     * keep their tokens), and Illuminate\Auth\AuthenticationException when the
     * service rejected the token (renders as 401). Illuminate HTTP client and
     * Guzzle connection exceptions, and RequestExceptions whose status is not
     * in the configured rejection set (default 400/401/403), are mapped to the
     * 503 path automatically; any other exception is reported and rendered as
     * a generic 401.
     *
     * @return array<string, mixed>
     */
    public function execute(AccessTokenInterface $token): array;
}
