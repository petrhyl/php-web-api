<?php

namespace WebApiCore\Container;

use Exception;
use WebApiCore\Container\Descriptor\ClassDescriptor;
use WebApiCore\Container\Provider\InstanceLifetime;
use WebApiCore\Container\Provider\InstanceProvider;

class Container
{
    private array $descriptors = [];

    /**
     * @param string $className is name of a class or interface which instance is provided by {@see WebApiCore\Container\Descriptor\ClassDescriptor}
     * @param ClassDescriptor $descriptor providing either a class instance or a function to create the instance by {@see WebApiCore\Container\Provider\InstanceProvider}
     */
    public function bindDescriptor(string $className, ClassDescriptor $descriptor): void
    {
        if ($this->isClassNameAdded($className)) {
            throw new \Exception("Provided key [$className] is already added.");
        }

        $this->descriptors[$className] = $descriptor;
    }

    /**
     * @param string $className is name of a class or interface which instance is provided by the $factory parameter.
     * @param callable $factory function which returns new instance of bound class. 
     * Receives a {@see WebApiCore\Container\Provider\InstanceProvider} as a parameter.
     * @param \WebApiCore\Container\Provider\InstanceLifetime $lifetime enum value of {@see WebApiCore\Container\Provider\InstanceLifetime}
     * @example : 
     * ```php
     * $builder = AppBuilder::createBuilder();
     * 
     * $builder->Container->bind(
     *  MyService::class, fn (\WebApiCore\Container\InstanceProvider $provider) => new MyService($args), InstanceLifetime::Scoped
     * );
     * 
     * ```
     */
    public function bind(string $className, callable $factory, InstanceLifetime $lifetime): void
    {
        if (!is_callable($factory)) {
            throw new Exception("The function for creating an instance of class [$className] is not provided.");
        }

        $descriptor = new ClassDescriptor($lifetime, $factory);

        if ($this->isClassNameAdded($className)) {
            throw new Exception("$className class is already added to the container.");
        }

        $this->bindDescriptor($className, $descriptor);
    }

    /**
     * @param string $className is name of a class or interface which instance is provided by the $factory parameter.
     * @param callable|null $factory function which returns new instance of bound class
     * * Receives a {@see WebApiCore\Container\Provider\InstanceProvider} as a parameter.
     * * If the $factory parameter is not provided, the instance and its constructor parameters will be build using reflection.
     * @example : 
     * ```php
     * $builder = AppBuilder::createBuilder();
     * 
     * $builder->Container->bindTransient(MyService::class, fn (\WebApiCore\Container\InstanceProvider $provider) => new MyService($args));
     * 
     * ```
     */
    public function bindTransient(string $className, ?callable $factory = null): void
    {
        if ($factory === null) {
            $factory = fn(InstanceProvider $provider) => $provider->build($className);
        }

        $this->bind($className, $factory, InstanceLifetime::Transient);
    }

    /**
     * @param string $className is name of a class or interface which instance is provided by the $factory parameter.
     * @param callable|null $factory function which returns new instance of bound class. 
     * * Receives a {@see WebApiCore\Container\Provider\InstanceProvider} as a parameter.
     * * If the $factory parameter is not provided, the instance and its constructor parameters will be build using reflection.
     * @example : 
     * ```php
     * $builder = AppBuilder::createBuilder();
     * 
     * $builder->Container->bindScoped(MyService::class, fn (\WebApiCore\Container\InstanceProvider $provider) => new MyService($args));
     * 
     * ```
     */
    public function bindScoped(string $className, ?callable $factory = null): void
    {
        if ($factory === null) {
            $factory = fn(InstanceProvider $provider) => $provider->build($className);
        }

        $this->bind($className, $factory, InstanceLifetime::Transient);
    }

    public function get(string $className): ClassDescriptor | null
    {
        if (!array_key_exists($className, $this->descriptors)) {
            return null;
        }

        return $this->descriptors[$className];
    }

    /**
     * If there is no class name provided by parameter `$existingClassName` in the container
     * no action will be executed otherwise old descriptor will be overridden by the new one.
     * @param string $existingClassName is name of a class or interface which instance is provided by {@see WebApiCore\Container\Descriptor\ClassDescriptor} and is already added to the container
     * @param ClassDescriptor $descriptor providing either a class instance or a function to create the instance by {@see WebApiCore\Container\Provider\InstanceProvider}
     * @return bool if there is a class provided by parameter `$existingClassName` in the container returns `true`
     * otherwise `false`
     */
    public function tryBindDescriptor(string $existingClassName, ClassDescriptor $descriptor): bool
    {
        if (!$this->isClassNameAdded($existingClassName)) {
            return false;
        }

        $this->descriptors[$existingClassName] = $descriptor;

        return true;
    }

    public function isClassNameAdded($className): bool
    {
        return array_key_exists($className, $this->descriptors);
    }
}
