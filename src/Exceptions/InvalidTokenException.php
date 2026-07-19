<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when the request carries no usable access token. Extends
 * InvalidArgumentException for backward compatibility, but gives the Adapter a
 * package-owned type to catch so it never swallows unrelated
 * InvalidArgumentExceptions (e.g. an undefined cache store).
 */
class InvalidTokenException extends InvalidArgumentException
{
}
