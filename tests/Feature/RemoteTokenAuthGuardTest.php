<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\MakeValidationRequestAction;
use Mockery\MockInterface;

use function Pest\Laravel\getJson;

it('can get the logged user from the request', function () {
    $user = ['id' => 1, 'name' => 'Tony Stark'];
    $this->instance(
        MakeValidationRequestAction::class,
        Mockery::mock(MakeValidationRequestAction::class, function (MockInterface $mock) use ($user) {
            $mock->shouldReceive('execute')->once()->andReturn($user);
        })
    );

    Route::get('/', function (Request $request) {
        return $request->user();
    })->middleware('auth:rta');

    $response = getJson('/', [
        'Authorization' => 'Bearer a0339966fec93f92ddfe2d04c1bd3a12db1aa18',
    ])->assertOk()->json();

    expect($user)->toEqual($response);
});
