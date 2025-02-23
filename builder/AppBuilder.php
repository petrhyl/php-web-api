<?php

namespace WebApiCore\Builder;

use Exception;
use WebApiCore\Container\Container;
use WebApiCore\Container\Provider\InstanceProvider;
use WebApiCore\Routes\EndpointRouteBuilder;

class AppBuilder
{
    private static ?AppBuilder $appBuilder = null;

    private function __construct()
    {
        $this->Container = new Container();
    }

    public readonly Container $Container;

    public static function createBuilder(): AppBuilder
    {
        if (self::$appBuilder !== null) {
            throw new Exception('Application builder is already created.');
        }

        self::$appBuilder = new AppBuilder();

        return self::$appBuilder;
    }

    public function buildApp(): App
    {
        $provider = new InstanceProvider($this->Container);

        $router = $provider->build(EndpointRouteBuilder::class);

        return new App($provider, $router);
    }
}
