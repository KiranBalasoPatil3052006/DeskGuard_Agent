<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Support\Str;

/**
 * BaseDTO
 *
 * Abstract base class for all Data Transfer Objects.
 * Provides a convention-driven hydration pattern: constructor
 * accepts an associative array and calls setter methods
 * (e.g. setMachineUid()) when they exist.
 */
abstract class BaseDTO
{
    /** Internal storage for hydrated properties */
    protected array $properties = [];

    /**
     * Hydrate the DTO from an associative array.
     * For each key, a camelCase setter (e.g. setMachineUid for machine_uid)
     * is called if the method exists on the concrete class.
     *
     * @param array $data Key-value pairs of property data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            // Convert snake_case key to camelCase setter name
            $setter = 'set' . Str::studly($key);
            if (method_exists($this, $setter)) {
                $this->{$setter}($value);
            }
        }
    }

    /**
     * Export the DTO properties as an associative array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->properties;
    }

    /**
     * Create a new DTO instance from an associative array.
     *
     * @param array $data Key-value pairs of property data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        return new static($data);
    }
}
