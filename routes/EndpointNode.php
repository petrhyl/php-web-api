<?php

namespace Core\Routes;

use Core\Interfaces\IMiddleware;

class EndpointNode
{
    private array $children = [];
    private array $childrenAsParams = [];
    private array $middlewares = [];

    public function __construct(
        public readonly string $name,
        public readonly bool $isParameter = false,
        private ?string $endpointClass = null
    ) {
        if (empty($name)) {
            throw new \Exception("Name has to be provided.");
        }
    }

    public array $parameterValues = [];


    /**
     * @throws \Exception if child `EndpointNode` is already set in this node either as a parameter node or as path node.
     */
    public function addChild(EndpointNode $endpointNode): void
    {
        if (array_key_exists($endpointNode->name, $this->children)) {
            throw new \Exception("child node with name [$endpointNode->name] already exists in this node.");
        }

        if ($endpointNode->isParameter) {
            if (array_key_exists($endpointNode->name, $this->childrenAsParams)) {
                throw new \Exception("child node with name [$endpointNode->name] already exists in this node.");
            }

            $this->childrenAsParams[$endpointNode->name] = $endpointNode;

            return;
        }

        $this->children[$endpointNode->name] = $endpointNode;
    }

    public function hasChildren(): bool
    {
        return count($this->children) > 0 || count($this->childrenAsParams) > 0;
    }

    /**
     * @param string $nodeName unique name of the searched node
     * @return EndpointNode or `null` if the child node is not found
     */
    public function getChildNode(string $nodeName): ?EndpointNode
    {
        if (array_key_exists($nodeName, $this->children)) {
            return $this->children[$nodeName];
        }

        if (array_key_exists($nodeName, $this->childrenAsParams)) {
            return $this->children[$nodeName];
        }

        return null;
    }

    public function containsChild(string $nodeName): bool
    {
        return $this->getChildNode($nodeName) !== null;
    }

    /**
     * @return EndpointNode[] associative array that contains keys as `EndpointNode`'s `name`
     */
    public function getChildNodesWhichAreParameter(): array
    {
        return $this->childrenAsParams;
    }

    public function getEndpointController(): string | null
    {
        return $this->endpointClass;
    }

    /**
     * @throws \Exception if `EndpointNode` has already its endpointClass or if the provided endpointClass is empty.
     */
    public function setEndpoint($endpointClass): void
    {
        if (empty($endpointClass)) {
            throw new \Exception("Provided endpoint class must not be empty");
        }

        if (!empty($this->endpointClass)) {
            throw new \Exception("This endpoint already has its class [$this->endpointClass]");
        }

        $this->endpointClass = $endpointClass;
    }

    public function addMiddleware(string $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * @param string[] $middlewares names of middlewares' classes
     */
    public function setMiddlewares(array $middlewares): void
    {
        $this->middlewares = array_merge($this->middlewares, $middlewares);
    }

    /**
     * @return string[] names of middlewares' classes
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}
