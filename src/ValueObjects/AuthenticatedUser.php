<?php

declare(strict_types=1);

namespace JorgeMudry\LaravelRemoteTokenAuth\ValueObjects;

use ArrayAccess;
use Exception;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;

/**
 * @implements ArrayAccess<string, mixed>
 * @implements Arrayable<string, mixed>
 */
class AuthenticatedUser extends GenericUser implements Arrayable, ArrayAccess, Jsonable, JsonSerializable
{
    /**
     * Create a new generic User object.
     *
     * @param  array<string, mixed>  $attributes
     * @return void
     */
    public function __construct(array $attributes)
    {
        $attributes['id'] = $attributes['id'] ?? null;

        parent::__construct($attributes);
    }

    /**
     * Get the instance as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Get attribute by key
     */
    public function getAttribute(string $key): mixed
    {
        $key = mb_trim($key);

        if (
            empty($key)
            || array_key_exists($key, $this->attributes) === false
        ) {
            return null;
        }

        return $this->attributes[$key];
    }

    /**
     * Determine if the given attribute exists.
     */
    public function offsetExists(mixed $offset): bool
    {
        return ($this->getAttribute((string) $offset) === null) === false;
    }

    /**
     * Get the value for a given offset.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->getAttribute((string) $offset);
    }

    /**
     * Set the value for a given offset.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->setAttribute((string) $offset, $value);
    }

    /**
     * Set a given attribute on the model.
     */
    public function setAttribute(string $key, mixed $value): self
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Unset the value for a given offset.
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->attributes[$offset]);
    }

    /**
     * Convert the model instance to JSON.
     *
     * @param int $options
     */
    public function toJson($options = 0): string
    {
        $json = json_encode($this->jsonSerialize(), $options);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(json_last_error_msg());
        }

        return strval($json);
    }

    /**
     * Convert the object into something JSON serializable.
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
