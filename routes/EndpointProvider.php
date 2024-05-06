<?php

namespace WebApiCore\Routes;

use WebApiCore\Exceptions\ApplicationException;

class EndpointProvider
{
    private array $paramValues;
    private readonly HttpMethodEndpointContainer $endpointContainer;

    public function __construct(
        private readonly EndpointRouteBuilder $routeBuilder
    ) {
        $this->endpointContainer = $routeBuilder->getEndpointContainer();
    }


    /**
     * @throws ApplicationException if the route or its endpoint is not found
     */
    public function resolve(string $method, string $path): EndpointResult
    {
        $this->paramValues = [];

        $method = strtolower($method);

        $pathParts = $this->routeBuilder->splitToPathParts($path);

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
