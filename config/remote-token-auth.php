<?php

declare(strict_types=1);

use JorgeMudry\LaravelRemoteTokenAuth\Actions\CreateUserFromAttributesAction;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\GetAttributesFromResponseAction;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\GetTokenFromRequestAction;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\MakeValidationRequestAction;
use JorgeMudry\LaravelRemoteTokenAuth\ValueObjects\AuthenticatedUser;

return [

    /*
     * Name of the auth guard registered by the package. Routes protect
     * themselves with "auth:<guard>".
     */
    'guard' => 'rta',

    /*
     * Endpoint of the external service that validates the token. The token is
     * sent as a bearer token in the Authorization header of a GET request.
     */
    'endpoint' => env('REMOTE_TOKEN_AUTH_ENDPOINT', ''),

    'http' => [
        /*
         * Timeouts (in seconds) for the validation request. They run on every
         * authenticated request, so keep them short.
         */
        'timeout' => env('REMOTE_TOKEN_AUTH_TIMEOUT', 5),
        'connect_timeout' => env('REMOTE_TOKEN_AUTH_CONNECT_TIMEOUT', 2),

        /*
         * Response statuses from the validation service that mean the token
         * was rejected (the client gets a 401). Any other error status is
         * treated as a service problem and produces a 503, so clients do not
         * discard still-valid tokens.
         */
        'rejection_statuses' => [400, 401, 403],
    ],

    'response' => [
        /*
         * Dot-notation path to the user attributes inside the validation
         * response. Leave empty to use the whole response body.
         */
        'user_path' => '',

        /*
         * Class used to represent the authenticated user. It must implement
         * Illuminate\Contracts\Auth\Authenticatable and accept the attributes
         * array as its only constructor argument.
         */
        'user_class' => AuthenticatedUser::class,
    ],

    'cache' => [
        /*
         * Cache successful validations to avoid one HTTP round-trip per
         * request. Trade-off: a token revoked upstream keeps working here
         * until the TTL (in seconds) expires.
         */
        'enabled' => env('REMOTE_TOKEN_AUTH_CACHE', false),
        'store' => null,
        'ttl' => 60,
        'prefix' => 'remote-token-auth',
    ],

    /*
     * The four steps of the authentication pipeline. Replace any of them with
     * your own class; each one must implement the matching contract from
     * JorgeMudry\LaravelRemoteTokenAuth\Contracts.
     */
    'actions' => [
        'token-resolver' => GetTokenFromRequestAction::class,
        'token-validator' => MakeValidationRequestAction::class,
        'attributes-resolver' => GetAttributesFromResponseAction::class,
        'user-maker' => CreateUserFromAttributesAction::class,
    ],
];
