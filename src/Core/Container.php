<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Simple dependency injection container
 */
class Container
{
    private array $services = [];
    private array $instances = [];

    /**
     * Register a service
     */
    public function set(string $id, callable $factory): void
    {
        $this->services[$id] = $factory;
    }

    /**
     * Get a service
     * 
     * @throws \Exception If service not found
     */
    public function get(string $id): object
    {
        if (!isset($this->instances[$id])) {
            if (!isset($this->services[$id])) {
                throw new \Exception("Service not found: $id");
            }
            $this->instances[$id] = $this->services[$id]($this);
        }
        return $this->instances[$id];
    }

    /**
     * Check if a service exists
     */
    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }
}
