<?php

declare(strict_types=1);

use JorgeMudry\LaravelRemoteTokenAuth\ValueObjects\AuthenticatedUser;

return [
    'endpoint' => 'https://randomuser.me/api/',
    'response' => [
        'user_path' => 'data',
        'user_class' => AuthenticatedUser::class
    ]
];
