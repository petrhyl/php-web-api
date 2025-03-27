<?php

namespace WebApiCore\Util;

class Constants
{
    public static function appParentDir(): string
    {
        return dirname(dirname(__DIR__));
    }
}
