<?php

namespace WebApiCore\Container;

class ServiceDescriptor
{
    public function __construct(public $factory = null, public ?object $instance = null)
    {
    }
}
