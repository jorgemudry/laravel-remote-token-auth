<?php

declare(strict_types=1);

use JorgeMudry\LaravelRemoteTokenAuth\Actions\ActionsResolver;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\CreateUserFromAttributesAction;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\GetAttributesFromResponseAction;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\GetTokenFromRequestAction;
use JorgeMudry\LaravelRemoteTokenAuth\Actions\MakeValidationRequestAction;

it('can resolve a GetTokenFromRequestAction instance', function (): void {
    config(['remote-token-auth.actions.token-resolver' => GetTokenFromRequestAction::class]);
    $resolver = new ActionsResolver();
    expect($resolver->getTokenResolver())->toBeInstanceOf(GetTokenFromRequestAction::class);
});

it('can resolve a MakeValidationRequestAction instance', function (): void {
    config(['remote-token-auth.actions.request-maker' => MakeValidationRequestAction::class]);
    $resolver = new ActionsResolver();
    expect($resolver->getRequestMaker())->toBeInstanceOf(MakeValidationRequestAction::class);
});

it('can resolve a GetAttributesFromResponseAction instance', function (): void {
    config(['remote-token-auth.actions.attributes-resolver' => GetAttributesFromResponseAction::class]);
    $resolver = new ActionsResolver();
    expect($resolver->getAttributesResolver())->toBeInstanceOf(GetAttributesFromResponseAction::class);
});

it('can resolve a CreateUserFromAttributesAction instance', function (): void {
    config(['remote-token-auth.actions.user-maker' => CreateUserFromAttributesAction::class]);
    $resolver = new ActionsResolver();
    expect($resolver->getUserMaker())->toBeInstanceOf(CreateUserFromAttributesAction::class);
});
