<?php

declare(strict_types=1);

namespace AgusSuroyo\Container;

use ReflectionClass;
use ReflectionParameter;
use InvalidArgumentException;
use RuntimeException;

class Container
{
    /** @var array<string, object> */
    private array $instances = [];

    /** @var array<string, callable> */
    private array $bindings = [];

    /**
     * Bind a concrete implementation to an interface
     *
     * @param string $abstract
     * @param callable|string $concrete (callable(): object)|class-string
     */
    public function bind(string $abstract, $concrete): void
    {
        if (is_string($concrete)) {
            /** @var class-string $concrete */
            $this->bindings[$abstract] = fn(): object => $this->make($concrete);
        } else {
            $this->bindings[$abstract] = $concrete;
        }
    }

    /**
     * Bind a singleton instance
     *
     * @param string $abstract
     * @param callable|string $concrete (callable(): object)|class-string
     */
    public function singleton(string $abstract, $concrete): void
    {
        $this->bind($abstract, $concrete);
    }

    /**
     * Get an instance from the container
     *
     * @template T of object
     * @param class-string<T>|string $abstract Class name or custom binding key
     * @return ($abstract is class-string<T> ? T : object)
     */
    public function get(string $abstract): object
    {
        // Return existing singleton
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Use custom binding
        if (isset($this->bindings[$abstract])) {
            $instance = $this->bindings[$abstract]();
            $this->instances[$abstract] = $instance;
            return $instance;
        }

        // Auto-resolve
        $instance = $this->make($abstract);
        $this->instances[$abstract] = $instance;
        return $instance;
    }

    /**
     * Make a new instance
     *
     * @param string $class Class name to instantiate
     * @return object
     */
    public function make(string $class): object
    {
        if (!class_exists($class)) {
            throw new InvalidArgumentException("Class {$class} not found");
        }

        $reflector = new ReflectionClass($class);

        if (!$reflector->isInstantiable()) {
            throw new RuntimeException("Class {$class} is not instantiable");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $dependencies = $this->resolveDependencies($constructor->getParameters());

        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * Resolve method dependencies
     *
     * @param ReflectionParameter[] $parameters
     * @return array<mixed>
     */
    private function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if ($type === null) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new RuntimeException(
                        "Cannot resolve parameter {$parameter->getName()}"
                    );
                }
                continue;
            }

            if (!($type instanceof \ReflectionNamedType)) {
                throw new RuntimeException(
                    "Cannot resolve union/intersection type for parameter {$parameter->getName()}"
                );
            }

            $typeName = $type->getName();

            if (!class_exists($typeName) && !interface_exists($typeName)) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new RuntimeException(
                        "Cannot resolve non-class parameter {$parameter->getName()}"
                    );
                }
                continue;
            }

            $dependencies[] = $this->get($typeName);
        }

        return $dependencies;
    }

    /**
     * Check if abstract is bound
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Clear a specific singleton instance or all instances
     *
     * @param string|null $abstract The abstract to clear, or null to clear all
     */
    public function clearInstance(?string $abstract = null): void
    {
        if ($abstract === null) {
            $this->instances = [];
        } else {
            unset($this->instances[$abstract]);
        }
    }
}
