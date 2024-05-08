<?php

namespace WebApiCore\Container;

use Exception;

class Container
{
    private array $descriptors = [];

    /**
     * @param string $className is name of a class or interface which instance is provided by @see {\WebApiCore\Container\ClassDescriptor}
     * @param ClassDescriptor $descriptor providing either class instance or callback to create instance with @see {\WebApiCore\Container\InstanceProvider}
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
     * Receives a @see{\WebApiCore\Container\InstanceProvider} as a parameter.
     * @param string $lifetime value of constant from @see{\WebApiCore\Container\InstanceLifetime}
     * @example : 
     * $container = new Container();
     * 
     * $container->bind(MyService::class, fn (\WebApiCore\Container\InstanceProvider $provider) => new MyService($args));
     */
    public function bind(string $className, callable $factory, string $lifetime): void
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
     * @param callable $factory function which returns new instance of bound class. 
     * Receives a @see{\WebApiCore\Container\InstanceProvider} as a parameter.
     * @example : 
     * $container = new Container();
     * 
     * $container->bind(MyService::class, fn (\WebApiCore\Container\InstanceProvider $provider) => new MyService($args));
     */
    public function bindTransient(string $className, callable $factory): void
    {
        $this->bind($className, $factory, InstanceLifetime::TRANSIENT);
    }

    /**
     * @param string $className is name of a class or interface which instance is provided by the $factory parameter.
     * @param callable $factory function which returns new instance of bound class. 
     * Receives a @see{\WebApiCore\Container\InstanceProvider} as a parameter.
     * @example : 
     * $container = new Container();
     * 
     * $container->bind(MyService::class, fn (\WebApiCore\Container\InstanceProvider $provider) => new MyService($args));
     */
    public function bindScoped(string $className, callable $factory): void
    {
        $this->bind($className, $factory, InstanceLifetime::SCOPED);
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
     * @param string $existingClassName is name of a class or interface which instance is provided by @see {\WebApiCore\Container\ClassDescriptor} and is already added to the container
     * @param ClassDescriptor $descriptor providing either class instance or callback to create instance of with @see {\WebApiCore\Container\InstanceProvider} of already added class
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
