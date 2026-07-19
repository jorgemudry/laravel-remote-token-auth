<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\Actions;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Authenticatable;
use JorgeMudry\LaravelRemoteTokenAuth\Contracts\CreatesUser;
use JorgeMudry\LaravelRemoteTokenAuth\Exceptions\InvalidUserResponseException;
use JorgeMudry\LaravelRemoteTokenAuth\Exceptions\MissingConfigurationException;
use JorgeMudry\LaravelRemoteTokenAuth\ValueObjects\AuthenticatedUser;

class CreateUserFromAttributesAction implements CreatesUser
{
    public function __construct(protected string $user_class = AuthenticatedUser::class)
    {
        if ( ! is_a($this->user_class, Authenticatable::class, true)) {
            throw new MissingConfigurationException(sprintf(
                'The configured user class [%s] must implement %s.',
                $this->user_class,
                Authenticatable::class,
            ));
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function execute(array $attributes): Authenticatable
    {
        if ($attributes === []) {
            throw new InvalidUserResponseException('Cannot create a user from an empty set of attributes.');
        }

        /** @var class-string<Authenticatable> $user_class */
        $user_class = $this->user_class;
        $user = new $user_class($attributes);
        $identifier_name = $user->getAuthIdentifierName();

        // GenericUser-based classes read the identifier straight from the
        // attributes array with no missing-key guard, so check the array
        // first instead of triggering an "Undefined array key" warning.
        $identifier_missing = $user instanceof GenericUser
            ? ($attributes[$identifier_name] ?? null) === null
            : $user->getAuthIdentifier() === null;

        if ($identifier_missing) {
            throw new InvalidUserResponseException(sprintf(
                'The user attributes are missing the auth identifier "%s".',
                $identifier_name,
            ));
        }

        return $user;
    }
}
