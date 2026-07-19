<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ValidationServiceUnavailableException extends HttpException
{
    public function __construct(
        string $message = 'The token validation service is unavailable.',
        ?Throwable $previous = null,
    ) {
        parent::__construct(503, $message, $previous);
    }
}
