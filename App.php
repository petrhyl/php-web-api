<?php

namespace Core;

use Core\Container\ServiceProvider;
use Core\Exceptions\ApplicationException;
use Core\Http\HttpRequest;
use Core\Routes\Callables\IMiddleware;
use Core\Routes\EndpointRouteBuilder;
use Core\Util\Converter;
use Exception;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

class App
{
    public function __construct(private readonly ServiceProvider $serviceProvider, private EndpointRouteBuilder $router)
    {
        if (empty($router)) {
            throw new \Exception("Missing router for executing the endpoints.");
        }
    }

    public static HttpRequest $request;

    /**
     * @var \Core\Interfaces\IMiddleware[] middlewares which are invoke for each request 
     */
    private array $middlewares = [];


    public function addRouter(EndpointRouteBuilder $router): void
    {
        $this->router = $router;
    }

    public function mapGet(string $path, string $controllerClass, array $middlewares): void
    {
        $this->router->get($path, $controllerClass, $middlewares);
    }

    public function mapPost(string $path, string $controllerClass, array $middlewares): void
    {
        $this->router->post($path, $controllerClass, $middlewares);
    }

    public function mapPut(string $path, string $controllerClass, array $middlewares): void
    {
        $this->router->put($path, $controllerClass, $middlewares);
    }

    public function mapDelete(string $path, string $controllerClass, array $middlewares): void
    {
        $this->router->delete($path, $controllerClass, $middlewares);
    }

    public function mapPatch(string $path, string $controllerClass, array $middlewares): void
    {
        $this->router->patch($path, $controllerClass, $middlewares);
    }

    public function useMiddleware(IMiddleware $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * @param string $class class implementing `IMiddleware` interface to execute invoke(`HttpRequest`, `fn (HttpRequest`) => void) method for any endpoint
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
     * @throws Exception
     */
    public function getInstance(string $className): object
    {
        try {
            $instance = $this->serviceProvider->get($className);

            return $instance;
        } catch (Exception $e) {
            return $this->serviceProvider->build($className);
        }
    }

    /**
     * @throws ApplicationException
     */
    public function run(): void
    {
        static::$request = $this->initRequest();

        if (static::$request->method === 'OPTIONS') {
            http_response_code(200);
            die();
        }

        $endpoint = $this->router->resolve(static::$request->method, static::$request->urlPath);

        $next = fn (HttpRequest $request) => static::$request = $request;

        foreach ($this->middlewares as $appMiddleware) {
            $appMiddleware->invoke(static::$request, $next);
        }

        foreach ($endpoint->middlewares as $middleware) {
            $middlewareInstance = $this->getInstance($middleware);
            $middlewareInstance->invoke(static::$request, $next);
        }

        $endpointInstance = $this->getInstance($endpoint->endpointClass);

        $invocation = new ReflectionMethod($endpointInstance::class, '__invoke');
        $params = $invocation->getParameters();

        $methodParams = $this->retrieveMethodParameters($params);

        $methodParams = array_merge($methodParams, $endpoint->paramValues);

        $invocation->invokeArgs($endpointInstance, $methodParams);
    }

    /**
     * @param ReflectionParameter[] $params array that contains endpoint's `invoke()` method parameters.
     * Type of parameter of name 'payload' will be use to convert request body.
     * Type of parameter of name 'query' will be use to convert request url query parameters.
     * @return KeyValuePair[] array that contains keys as names of method's parameters ('payload' or 'query') and values as parameters' values
     * @throws ApplicationException
     */
    private function retrieveMethodParameters(array $params): array
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
                } catch (\Throwable $th) {
                    throw new ApplicationException("Missing or bad formatted required query parameter for the endpoint.", 400, $th->getCode(), [], $th);
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
        $host =  gethostname();

        if (!empty($host)) {
            $request->host = $host;
        }

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
