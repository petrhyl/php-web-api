<?php

namespace WebApiCore\Builder;

use Exception;
use WebApiCore\Configuration\ConfigurationManager;
use WebApiCore\Configuration\ConfigurationOptions;
use WebApiCore\Container\Container;
use WebApiCore\Container\Instance\Provider\InstanceProvider;
use WebApiCore\Routes\EndpointRouteBuilder;

class AppBuilder
{
    private static ?AppBuilder $appBuilder = null;
    public readonly Container $Container;
    public readonly ConfigurationManager $Configuration;

    private function __construct(
        ?ConfigurationOptions $options = null
    ) {
        $this->Container = new Container();
        $this->Configuration = new ConfigurationManager($options);
    }

    public static function createBuilder(?ConfigurationOptions $options = null): AppBuilder
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
