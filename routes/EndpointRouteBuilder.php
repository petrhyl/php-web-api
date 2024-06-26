<?php

namespace WebApiCore\Routes;

use WebApiCore\Routes\Callables\IMiddleware;
use WebApiCore\Routes\EndpointNode;
use ReflectionClass;

class EndpointRouteBuilder
{
    public function __construct(
        private HttpMethodEndpointContainer $endpointContainer)
    {
    }


    public function getEndpointContainer() : HttpMethodEndpointContainer {
        return $this->endpointContainer;
    }

    /**
     * @param string $path path of an endpoint - might contains parameter's name written between curly braces
     * 
     * @param string $endpointClass fully qualified name of the endpoint class 
     * - endpoint's class has to contain magic method `__invoke()`
     * - `__invoke()` method may have parameters named as:
     * 1) `payload` - this is request body object
     * 2) `query` - this is object created from url query params
     * 3)  parameter written in url path to this endpoint
     * 
     * @param string[] $middlewares fully qualified class' names of middlewares that will be called before the endpoint is called. 
     * Has to implements @see {WebApiCore\Routes\Callables\IMiddleware} whose `invoke()` method will be called
     */
    public function get(string $path, string $endpointClass, array $middlewares = []): void
    {
        $this->add('get', $path, $endpointClass, $middlewares);
    }

     /**
     * @param string $path path of an endpoint - might contains parameter's name written between curly braces
     * 
     * @param string $endpointClass fully qualified name of the endpoint class 
     * - endpoint's class has to contain magic method `__invoke()`
     * - `__invoke()` method may have parameters named as:
     * 1) `payload` - this is request body object
     * 2) `query` - this is object created from url query params
     * 3)  parameter written in url path to this endpoint
     * 
     * @param string[] $middlewares fully qualified class' names of middlewares that will be called before the endpoint is called. 
     * Has to implements @see {WebApiCore\Routes\Callables\IMiddleware} whose `invoke()` method will be called
     */
    public function post(string $path, string $endpointClass, array $middlewares = []): void
    {
        $this->add('post', $path, $endpointClass, $middlewares);
    }

     /**
     * @param string $path path of an endpoint - might contains parameter's name written between curly braces
     * 
     * @param string $endpointClass fully qualified name of the endpoint class 
     * - endpoint's class has to contain magic method `__invoke()`
     * - `__invoke()` method may have parameters named as:
     * 1) `payload` - this is request body object
     * 2) `query` - this is object created from url query params
     * 3)  parameter written in url path to this endpoint
     * 
     * @param string[] $middlewares fully qualified class' names of middlewares that will be called before the endpoint is called. 
     * Has to implements @see {WebApiCore\Routes\Callables\IMiddleware} whose `invoke()` method will be called
     */
    public function put(string $path, string $endpointClass, array $middlewares = []): void
    {
        $this->add('put', $path, $endpointClass, $middlewares);
    }

     /**
     * @param string $path path of an endpoint - might contains parameter's name written between curly braces
     * 
     * @param string $endpointClass fully qualified name of the endpoint class 
     * - endpoint's class has to contain magic method `__invoke()`
     * - `__invoke()` method may have parameters named as:
     * 1) `payload` - this is request body object
     * 2) `query` - this is object created from url query params
     * 3)  parameter written in url path to this endpoint
     * 
     * @param string[] $middlewares fully qualified class' names of middlewares that will be called before the endpoint is called. 
     * Has to implements @see {WebApiCore\Routes\Callables\IMiddleware} whose `invoke()` method will be called
     */
    public function patch(string $path, string $endpointClass, array $middlewares = []): void
    {
        $this->add('patch', $path, $endpointClass, $middlewares);
    }

     /**
     * @param string $path path of an endpoint - might contains parameter's name written between curly braces
     * 
     * @param string $endpointClass fully qualified name of the endpoint class 
     * - endpoint's class has to contain magic method `__invoke()`
     * - `__invoke()` method may have parameters named as:
     * 1) `payload` - this is request body object
     * 2) `query` - this is object created from url query params
     * 3)  parameter written in url path to this endpoint
     * 
     * @param string[] $middlewares fully qualified class' names of middlewares that will be called before the endpoint is called. 
     * Has to implements @see {WebApiCore\Routes\Callables\IMiddleware} whose `invoke()` method will be called
     */
    public function delete(string $path, string $endpointClass, array $middlewares = []): void
    {
        $this->add('delete', $path, $endpointClass, $middlewares);
    }

    public function splitToPathParts($urlPath): array
    {
        $urlPath = trim($urlPath);
        $urlPath = str_replace("\\", "/", $urlPath);
        $urlPath = trim($urlPath, "/");

        return explode("/", $urlPath);
    }

    private function add(string $method, string $path, string $endpointClass, array $middlewares = []): void
    {
        foreach ($middlewares as $middleware) {
            $middlewareInstance = new ReflectionClass($middleware);
            if (!$middlewareInstance->implementsInterface(IMiddleware::class)) {
                throw new \Exception("Bound middleware of type " . $middleware . " does not implement interface " . IMiddleware::class);
            }
        }

        $pathParts = $this->splitToPathParts($path);

        $node = $this->endpointContainer->getMethodEndpointNode(strtolower($method));

        for ($indx = 0; $indx < count($pathParts); $indx++) {
            $pathPart = $pathParts[$indx];

            if (empty($pathPart)) {
                if (count($pathParts) > 1) {
                    throw new \Exception("It is not possible to add empty path part to an endpoint.");
                }

                break;
            }

            $isParam = preg_match('/^{\w+}$/', $pathPart);

            if ($isParam) {
                $pathPart = str_replace(['{', '}'], '', $pathPart);
            } else {
                $pathPart = strtolower($pathPart);
            }

            $child = $node->getChildNode($pathPart);

            if ($child === null) {
                $child = new EndpointNode($pathPart, $isParam);

                $node->addChild($child);
            }

            $node = $child;
        }

        $node->setEndpoint($endpointClass);
        $node->setMiddlewares($middlewares);
    }
}
