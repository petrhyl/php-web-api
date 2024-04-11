<?php

namespace WebApiCore\Routes;

use WebApiCore\Routes\EndpointNode;

class HttpMethodEndpointContainer
{
    private array $methods = [
        'get',
        'post',
        'delete',
        'put',
        'patch',
        'connect',
        'options',
        'trace',
        'head'
    ];

    private array $endpointNodes = [];

    public function __construct()
    {
        for ($indx = 0; $indx < count($this->methods); $indx++) {
            $method = $this->methods[$indx];
            $this->endpointNodes[$method] = new EndpointNode($method);
        }
    }

    public function getMethodEndpointNode(string $method): EndpointNode
    {
        $method = strtolower($method);
        return $this->endpointNodes[$method];
    }
}
