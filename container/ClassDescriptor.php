<?php

namespace WebApiCore\Container;

use Exception;

class ClassDescriptor
{
    /**
     * @param string $lifetime constant's value of class @see `InstanceLifetime`
     */
    public function __construct(public readonly string $lifetime, public $factory = null, public ?object $instance = null)
    {

        if ($lifetime !== InstanceLifetime::TRANSIENT && $lifetime !== InstanceLifetime::SCOPED) {
            throw new Exception('Not acceptable value of [$lifetime] parameter.');
        }
    }
}
