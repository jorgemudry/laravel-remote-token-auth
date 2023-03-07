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
        $key = trim($key);

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
     *
     * @param mixed $offset
     */
    public function offsetExists($offset): bool
    {
        return ($this->getAttribute($offset) === null) === false;
    }

    /**
     * Get the value for a given offset.
     *
     * @param mixed $offset
     */
    public function offsetGet($offset): mixed
    {
        return $this->getAttribute($offset);
    }

    /**
     * Set the value for a given offset.
     *
     * @param  mixed  $offset
     * @param  mixed  $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->setAttribute($offset, $value);
    }

    /**
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed  $value
     */
    public function setAttribute($key, $value): self
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Unset the value for a given offset.
     *
     * @param  mixed  $offset
     */
    public function offsetUnset($offset): void
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
