<?php

namespace WebApiCore\Container\Instance\Provider;

enum InstanceLifetime
{
    case Transient;
    case Scoped;
}
