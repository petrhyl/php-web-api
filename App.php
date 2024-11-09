<?php

namespace WebApiCore;

use WebApiCore\Exceptions\ApplicationException;
use WebApiCore\Http\HttpRequest;
use WebApiCore\Routes\Callables\IMiddleware;
use WebApiCore\Routes\EndpointRouteBuilder;
use WebApiCore\Util\Converter;
use Exception;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use WebApiCore\Container\InstanceProvider;
use WebApiCore\Routes\EndpointProvider;
use WebApiCore\Routes\EndpointResult;

class App
{
    public function __construct(
        private readonly InstanceProvider $instanceProvider,
        private EndpointRouteBuilder $router
    ) {
        if (empty($router)) {
            throw new \Exception("Missing router for executing the endpoints.");
        }
    }

    public static HttpRequest $request;

    /**
     * @var \WebApiCore\Interfaces\IMiddleware[] middlewares which are invoke for each request 
     */
    private array $middlewares = [];


    public function addRouter(EndpointRouteBuilder $router): void
    {
        $this->router = $router;
    }

    /**
     * Register middleware that will be called on every request before calling endpoint's middlewares.
     */
    public function useMiddleware(IMiddleware $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Register middleware's class that will be instantiated from DI container.
     * @param string $class class implementing `IMiddleware` interface to execute 
     * `invoke(@see {WebApiCore\Http\HttpRequest}, fn (@see {WebApiCore\Http\HttpRequest}) => void)` method on every request.
     */
    public function useMiddlewareOfType(string $class): void
    {
        $reflectionClass = new ReflectionClass($class);

        if (!$reflectionClass->implementsInterface(IMiddleware::class)) {
            throw new Exception("Not implement interface " . IMiddleware::class);
        }

        $this->useMiddleware($this->getInstance($class));
    }

    /**
     * @param string $className fully-qualified name of required class
     * @return object instance of a class with all its dependecies from DI container 
     * or create a new instance if the class is not registered in the container.
     * @throws Exception
     */
    public function getInstance(string $className): object
    {
        try {
            $instance = $this->instanceProvider->get($className);

            return $instance;
        } catch (Exception $e) {
            return $this->instanceProvider->build($className);
        }
    }

    /**
     * Process request to this API - create @see{WebApiCore\Http\HttpRequest}
     * @throws ApplicationException
     */
    public function process(): void
    {
        static::$request = $this->initRequest();

        if (static::$request->method === 'OPTIONS') {
            http_response_code(200);
            die();
        }

        $endpointProvider = new EndpointProvider($this->router);
        $endpoint = $endpointProvider->resolve(static::$request->method, static::$request->urlPath);

        $this->invokeAllMiddlewares($endpoint->middlewares);

        $this->invokeEndpoint($endpoint);
    }


    private function invokeEndpoint(EndpointResult $endpoint): void
    {
        $endpointInstance = $this->getInstance($endpoint->endpointClass);

        $invocationMethod = new ReflectionMethod($endpointInstance::class, '__invoke');
        $params = $invocationMethod->getParameters();

        $methodParams = $this->retrieveValuesForPayloadAndQuery($params);

        $methodParams = array_merge($methodParams, $endpoint->paramValues);

        $invocationMethod->invokeArgs($endpointInstance, $methodParams);
    }

    /**
     * Invokes app middlewares and also endpoint middlewares
     * @param string[] $endpointMiddlewares classes of middlewares bound to the processing endpoint
     */
    private function invokeAllMiddlewares(array $endpointMiddlewares): void
    {
        $next = fn (HttpRequest $request) => static::$request = $request;

        foreach ($this->middlewares as $appMiddleware) {
            $appMiddleware->invoke(static::$request, $next);
        }

        foreach ($endpointMiddlewares as $middleware) {
            $middlewareInstance = $this->getInstance($middleware);
            $middlewareInstance->invoke(static::$request, $next);
        }
    }

    /**
     * @param ReflectionParameter[] $params array that contains endpoint's `invoke()` method parameters.
     * Type of parameter of name 'payload' will be use to convert request body.
     * Type of parameter of name 'query' will be use to convert request url query parameters.
     * @return array contains keys as names of method's parameters ('payload' or 'query') and values as parameters' values
     * @throws ApplicationException
     */
    private function retrieveValuesForPayloadAndQuery(array $params): array
    {
        $paramValues = [];

        foreach ($params as $param) {
            $paramName = $param->getName();
            if ($paramName === 'payload') {
                try {
                    $payload = $this->retrieveDataForParameter(static::$request->body, $param);
                } catch (\Throwable $th) {
                    throw new ApplicationException("Missing or bad formatted required payload for the endpoint.", 400, $th->getCode(), [], $th);
                }

                $paramValues[$paramName] = $payload;
            }

            if ($paramName == 'query') {
                try {
                    $query = $this->retrieveDataForParameter(static::$request->queryParams, $param);

                    $paramValues[$paramName] = $query;
                }catch(ApplicationException $appEx){
                    if (!$param->isOptional()) {
                        throw new ApplicationException("Missing or bad formatted required query parameter for the endpoint.", 400, 101, [], $appEx);
                    }
                } catch (\Throwable $th) {
                    throw new ApplicationException("Missing or bad formatted required query parameter for the endpoint.", 400, 101, [], $th);
                }
            }
        }

        return $paramValues;
    }

    private function retrieveDataForParameter(?array $data, ReflectionParameter $param): mixed
    {
        if (empty($data)) {
            throw new ApplicationException("Missing required request's data.", 400);
        }

        $paramName = $param->getName();
        $type = $param->getType();

        if ($type === null || $type->isBuiltin() === true) {
            if (!array_key_exists($paramName, $data)) {
                throw new \Exception("Not able to serialize request body into endpoint parameter", 100);
            }

            return $data[$paramName];
        }

        return Converter::convertAssocArrayToObject($type->getName(), $data);
    }

    private function initRequest(): HttpRequest
    {
        $request = new HttpRequest();

        $method = $_SERVER['REQUEST_METHOD'];

        $request->method = $method;
        $request->urlPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $request->host = $_SERVER['HTTP_HOST'];
        $request->userAgent = $_SERVER['HTTP_USER_AGENT'];

        $query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
        if (!empty($query)) {
            parse_str($query, $queries);
            $request->queryParams = $queries;
        }

        try {
            $request->body = json_decode(file_get_contents("php://input"), true);
        } catch (\Throwable $th) {
            $request->body = null;
        }

        $headers = getallheaders();

        if (!empty($headers)) {
            $request->headers = $headers;
        }

        return $request;
    }
}
