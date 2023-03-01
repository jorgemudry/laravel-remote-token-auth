<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

interface AdapterInterface
{
    /**
     * Validate the token and return the user
     */
    public function authorize(Request $request): Authenticatable;
}
