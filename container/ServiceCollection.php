<?php

namespace Core\Container;

class ServiceCollection
{
    private array $data = [];

    public function add(string $className, ServiceDescriptor $value): void
    {
        if ($this->isClassNameAdded($className)) {
            throw new \Exception("Provided key [$className] is already added.");
        }

        $this->data[$className] = $value;
    }

    public function get(string $className): ServiceDescriptor | null
    {
        if (!array_key_exists($className, $this->data)) {
            return null;
        }

        return $this->data[$className];
    }

    /**
     * If there is no class name provided by parameter `$existingClassName` in the collection
     * no action will be executed otherwise old descriptor will be overridden by the new one.
     * @param ServiceDescriptor $value new descriptor for already added class
     * @return bool if there is no class name provided by parameter `$existingClassName` in the collection returns `false`
     * otherwise `true`
     */
    public function trySetValue(string $existingClassName, ServiceDescriptor $value): bool
    {
        if (!$this->isClassNameAdded($existingClassName)) {
            return false;
        }

        $this->data[$existingClassName] = $value;

        return true;
    }

    public function isClassNameAdded($className): bool
    {
        return array_key_exists($className, $this->data);
    }
}
