<?php

namespace WebApiCore\Container;

use WebApiCore\App;
use Exception;
use WebApiCore\Container\ServiceCollection;
use WebApiCore\Container\ServiceDescriptor;
use WebApiCore\Routes\EndpointRouteBuilder;

class Container
{
    private readonly ServiceCollection $services;

    public function __construct()
    {
        $this->services = new ServiceCollection();
    }

    /**
     * @param string $name is name of the class or interface which class provided by the $factory parameter implements.
     * @param callable $factory function which returns new instance of bound class. Receives a `ServiceProvider` as a parameter.
     */
    public function bind(string $name, callable $factory): void
    {
        if (!is_callable($factory)) {
            throw new Exception("the function for creating an instance of class [$name] is not provided.");
        }

        $descriptor = new ServiceDescriptor($factory);

        if ($this->services->isClassNameAdded($name)) {
            throw new Exception("$name class is already added to the container.");
        }

        $this->services->add($name, $descriptor);
    }


    public function buildApp(): App
    {
        $provider = new ServiceProvider($this->services);

        $router = $provider->build(EndpointRouteBuilder::class);

        return new App($provider, $router);
    }
}
