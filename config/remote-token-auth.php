<?php

declare(strict_types=1);

use JorgeMudry\LaravelRemoteTokenAuth\ValueObjects\AuthenticatedUser;

/*
 * You can place your custom package configuration in here.
 */
return [
    'endpoint' => 'https://randomuser.me/api/',
    'response' => [
        'user_path' => 'data',
        'user_class' => AuthenticatedUser::class
    ]
];
