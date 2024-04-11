<?php

namespace Core\Container;

use ArgumentCountError;
use Exception;
use Core\Container\ServiceCollection;
use Core\Container\ServiceDescriptor;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

class ServiceProvider
{
    public function __construct(private readonly ServiceCollection $serviceCollection)
    {
        if (empty($serviceCollection)) {
            throw new ArgumentCountError(ServiceCollection::class . " object is not instantiated.");
        }
    }

    public function get(string $class): object
    {
        $descriptor = $this->serviceCollection->get($class);

        if ($descriptor === null) {
            throw new Exception("Target binding [$class] does not exist.");
        }

        $instance = $descriptor->instance;
        if ($instance === null) {
            if ($descriptor->factory === null) {
                throw new Exception("Missing factory for instantiate $class class.");
            }

            $instance = call_user_func($descriptor->factory, $this);
        }

        $reflectorOfInstance = new ReflectionClass($instance);

        if ($class !== $reflectorOfInstance->getName() && !$reflectorOfInstance->implementsInterface($class)) {
            throw new Exception("Implamentation factory did not return instance of class [$class].");
        }

        return $instance;
    }


    public function build(string $class)
    {
        try {
            $reflector = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new Exception("Target class [$class] does not exist.", 0, $e);
        }

        // If the type is not instantiable, such as an Interface or Abstract Class
        if (!$reflector->isInstantiable()) {
            throw new Exception("Target [$class] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        // If there are no constructor, that means there are no dependencies
        if ($constructor === null) {
            return new $class;
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                // Resolve a non-class hinted primitive dependency.
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else if ($parameter->isVariadic()) {
                    $dependencies[] = [];
                } else {
                    throw new Exception("Unresolvable dependency [$parameter] in class {$parameter->getDeclaringClass()->getName()}");
                }
            }

            $name = $type->getName();

            // Resolve a class based dependency from the container.
            try {
                $dependency = $this->get($name);
                $dependencies[] = $dependency;
            } catch (Exception $e) {
                if ($parameter->isOptional()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    $dependency = $this->build($name);
                    $descriptor = new ServiceDescriptor(fn () => $this->build($name));
                    $this->serviceCollection->add($name, $descriptor);
                    $dependencies[] = $dependency;
                }
            }
        }

        $instance = $reflector->newInstanceArgs($dependencies);

        if ($instance === null) {
            throw new Exception("Instantiation of class [$class] failed.");
        }

        return $instance;
    }
}
