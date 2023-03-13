<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use JorgeMudry\LaravelRemoteTokenAuth\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class)->in('Feature', 'Unit');

function createRequest(
    string $method,
    string $uri,
    array $parameters = [],
    array $cookies = [],
    array $files = [],
    array $server = [],
    mixed $content = null,
): Request {
    $base = SymfonyRequest::create($uri, $method, $parameters, $cookies, $files, $server, $content);

    return Request::createFromBase($base);
}
