<?php

declare(strict_types=1);

use JorgeMudry\LaravelRemoteTokenAuth\Actions\GetTokenFromRequestAction;
use JorgeMudry\LaravelRemoteTokenAuth\ValueObjects\AuthenticatedUser;

return [
    'endpoint' => 'https://dummyjson.com/users/1',
    'response' => [
        'user_path' => '',
        'user_class' => AuthenticatedUser::class
    ],
    'actions' => [
        'token-resolver' => GetTokenFromRequestAction::class
    ],
];
