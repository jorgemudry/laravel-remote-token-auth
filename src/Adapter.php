<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth;

use GuzzleHttp\Exception\ConnectException as GuzzleConnectException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\AdapterInterface;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\CreatesUser;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\ResolvesAttributes;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\ResolvesToken;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\ValidatesToken;
use JorgeMudry\LaravelRemoteTokenAuth\Exceptions\InvalidTokenException;
use JorgeMudry\LaravelRemoteTokenAuth\Exceptions\ValidationServiceUnavailableException;
use Throwable;

class Adapter implements AdapterInterface
{
    /**
     * @param array<int, int> $rejection_statuses HTTP statuses from the
     *        validation service that mean "token rejected" (401) rather than
     *        "service problem" (503).
     */
    public function __construct(
        protected ResolvesToken $token_resolver,
        protected ValidatesToken $token_validator,
        protected ResolvesAttributes $attributes_resolver,
        protected CreatesUser $user_maker,
        protected string $guard = 'rta',
        protected array $rejection_statuses = [400, 401, 403],
    ) {
    }

    /**
     * Authorize the user's token using an external service.
     *
     * Failures are split in two groups: a missing token or a rejection status
     * from the validation service (default 400/401/403) become an
     * AuthenticationException (401) with a generic message, while every other
     * failure — service unreachable, timeouts, 5xx, and unexpected statuses
     * like 404 or 429 — becomes a
     * ValidationServiceUnavailableException (503) so clients do not discard
     * still-valid tokens because of a service-side problem. Everything except
     * the plain missing-token and explicit-rejection cases is report()ed.
     *
     * @throws AuthenticationException
     * @throws ValidationServiceUnavailableException
     */
    public function authorize(Request $request): Authenticatable
    {
        try {
            $token = $this->token_resolver->execute($request);
            $response = $this->token_validator->execute($token);
            $attributes = $this->attributes_resolver->execute($response);

            return $this->user_maker->execute($attributes);
        } catch (AuthenticationException | ValidationServiceUnavailableException $exception) {
            throw $exception;
        } catch (ConnectionException | GuzzleConnectException $exception) {
            report($exception);

            throw new ValidationServiceUnavailableException(previous: $exception);
        } catch (RequestException $exception) {
            if (in_array($exception->response->status(), $this->rejection_statuses, true)) {
                // The service explicitly rejected the token.
                throw $this->deny();
            }

            // Any other status (404 mistyped endpoint, 405, 429 rate limit,
            // 5xx) is a service-side problem: the token may still be valid.
            report($exception);

            throw new ValidationServiceUnavailableException(previous: $exception);
        } catch (InvalidTokenException) {
            // A missing or malformed bearer token is a plain client error and
            // not worth reporting.
            throw $this->deny();
        } catch (Throwable $exception) {
            report($exception);

            throw $this->deny();
        }
    }

    protected function deny(): AuthenticationException
    {
        return new AuthenticationException('Invalid access token.', [$this->guard]);
    }
}
