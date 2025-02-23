<?php

namespace WebApiCore\Container\Provider;

enum InstanceLifetime
{
    case Transient;
    case Scoped;
}
