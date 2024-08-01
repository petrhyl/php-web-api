<?php

namespace WebApiCore\Routes;

class EndpointResult
{
    /**
     * @param string $endpointClass to be initialized from `InstanceProvider`
     * @param array $paramValues assoc array that contains keys as url parameters' names got from endpoint path.
     * @param string[] $middlewares contains middlewares' classes to be instantiate for this endpoint
     */
    public function __construct(
        public string $endpointClass,
        public array $paramValues,
        public array $middlewares
    ) {
    }
}
