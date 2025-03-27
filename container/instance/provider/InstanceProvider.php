<?php

namespace WebApiCore\Container\Instance\Provider;

use ArgumentCountError;
use Exception;
use WebApiCore\Container\Container;
use WebApiCore\Container\Descriptor\ClassDescriptor;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

class InstanceProvider
{
    public function __construct(private readonly Container $container)
    {
        if (empty($container)) {
            throw new ArgumentCountError(Container::class . " object is not instantiated.");
        }
    }

    public function get(string $class): object
    {
        $descriptor = $this->container->get($class);

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

        if ($descriptor->lifetime === InstanceLifetime::Scoped) {
            $descriptor->instance = $instance;
            $this->container->tryBindDescriptor($class, $descriptor);
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
                    throw new Exception("Unresolvable dependency of $parameter in class {$parameter->getDeclaringClass()->getName()}");
                }
            }

            $name = $type->getName();

            // Resolve a class based dependency from the container.
            if ($this->container->isClassNameAdded($name)) {
                $dependency = $this->get($name);
                $dependencies[] = $dependency;
            } elseif ($parameter->isOptional()) {
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                $dependency = $this->build($name);
                $descriptor = new ClassDescriptor(InstanceLifetime::Transient, fn () => $this->build($name));
                $this->container->bindDescriptor($name, $descriptor);
                $dependencies[] = $dependency;
            }
        }

        $instance = $reflector->newInstanceArgs($dependencies);

        if ($instance === null) {
            throw new Exception("Instantiation of class [$class] failed.");
        }

        return $instance;
    }
}
