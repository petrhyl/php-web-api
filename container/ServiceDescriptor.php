<?php

namespace Core\Container;

class ServiceDescriptor
{
    public function __construct(public $factory = null, public ?object $instance = null)
    {
    }
}
