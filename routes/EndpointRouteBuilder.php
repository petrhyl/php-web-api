<?php

namespace WebApiCore\Routes;

use WebApiCore\Exceptions\ApplicationException;
use WebApiCore\Routes\Callables\IMiddleware;
use WebApiCore\Routes\EndpointResult;
use WebApiCore\Routes\EndpointNode;
use ReflectionClass;

class EndpointRouteBuilder
{
    private array $paramValues;

    public function __construct(private HttpMethodEndpointContainer $endpointContainer)
    {
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

            $pathPart = strtolower($pathPart);

            $isParam = preg_match('/^{\w+}$/', $pathPart);

            if ($isParam) {
                $pathPart = str_replace(['{', '}'], '', $pathPart);
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

    /**
     * @throws ApplicationException if the route or its endpoint is not found
     */
    public function resolve(string $method, string $path): EndpointResult
    {
        $this->paramValues = [];

        $method = strtolower($method);

        $pathParts = $this->splitToPathParts($path);

        $node = $this->endpointContainer->getMethodEndpointNode(strtolower($method));

        $partsCount = count($pathParts);

        $node = $this->getChildNode($node, $pathParts, $partsCount, 0);

        if ($node === null) {
            throw new ApplicationException("Endpoint was not found.", 404);
        }

        $endpointClass = $node->getEndpointController();

        if ($endpointClass === null) {
            throw new ApplicationException("Endpoint was not found.", 404);
        }

        return new EndpointResult($endpointClass, $this->paramValues, $node->getMiddlewares());
    }

    private function splitToPathParts($urlPath): array
    {
        $urlPath = trim($urlPath);
        $urlPath = str_replace("\\", "/", $urlPath);
        $urlPath = trim($urlPath, "/");

        return explode("/", $urlPath);
    }

    private function getChildNode(EndpointNode $node, array $pathParts, int $partsCount, int $currentIndexOfParts): ?EndpointNode
    {
        if ($node->isParameter && $currentIndexOfParts > 0) {
            $this->paramValues[$node->name] = strtolower($pathParts[$currentIndexOfParts - 1]);
        }

        if ($currentIndexOfParts > $partsCount - 1) {
            return $node;
        }

        $pathPart = $pathParts[$currentIndexOfParts];

        if (empty($pathPart)) {
            if ($partsCount > 1) {
                throw new ApplicationException("Unprocessable URL part", 400);
            }

            return $node;
        }

        $pathPart = strtolower($pathPart);

        $child = $node->getChildNode($pathPart);

        if ($child === null) {
            $child = $this->getChildNodeWichIsParameter($node, $pathParts, $partsCount, $currentIndexOfParts);
        } else {
            $child = $this->getChildNode($child, $pathParts, $partsCount, ++$currentIndexOfParts);
        }

        return $child;
    }

    private function getChildNodeWichIsParameter(EndpointNode $node, array $pathParts, int $partsCount, int $currentIndexOfParts): ?EndpointNode
    {
        $params = $node->getChildNodesWhichAreParameter();
        $child = null;

        foreach ($params as $key => $value) {
            $child = $this->getChildNode($value, $pathParts, $partsCount, ++$currentIndexOfParts);

            if ($child !== null) {
                break;
            }
        }

        return $child;
    }
}
