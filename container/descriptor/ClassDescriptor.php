<?php

namespace WebApiCore\Container\Descriptor;

use Exception;
use WebApiCore\Container\Provider\InstanceLifetime;

class ClassDescriptor
{
    /**
     * @param \WebApiCore\Container\Provider\InstanceLifetime $lifetime enum value of {@see WebApiCore\Container\Provider\InstanceLifetime}
    * @param callable|null $factory function which receives {@see WebApiCore\Container\Provider\InstanceProvider} as a parameter and returns a new instance of the bound class.
     * @param object|null $instance instance of the bound class.
     */
    public function __construct(public readonly InstanceLifetime $lifetime, public $factory = null, public ?object $instance = null)
    {
        if ($factory === null && $instance === null) {
            throw new Exception("Either factory or instance must be provided.");
        }
    }
}
