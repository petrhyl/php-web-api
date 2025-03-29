<?php

namespace WebApiCore\Util;

use ReflectionClass;
use ReflectionProperty;

class Converter
{
    /**
     * @param $class class name that the `Converter` converts the array to its instance. The class must not have constructor with parameters.
     * @param $assocArray associative array which keys will be converted as property names of the given class
     * @return object of type of provided class
     * @throws Exception if it is not posible convert provided array into required object
     */
    public static function convertAssocArrayToObject(string $class, array $assocArray): object
    {
        $classReflection = new ReflectionClass($class);

        $properties = $classReflection->getProperties();

        $classInstance = $classReflection->newInstance();

        foreach ($properties as $prop) {
            $propName = $prop->getName();
            $propType = $prop->getType();

            if ($propType === null) {
                throw new \Exception("Converting property's type cannot be null");
            }

            if (!array_key_exists($propName, $assocArray)) {
                if ($propType->allowsNull() || $prop->hasDefaultValue()) {
                    continue;
                }

                throw new \Exception("Not able to deserialize array into type of '" . $classInstance::class . "'. Missing value for property '$propName'");
            }

            $dataValue = $assocArray[$propName];
            $propValue = null;

            if ($propType->isBuiltin()) {
                $propValue = self::resolveBuildInTypeValue($dataValue, $prop);
            } else {
                $propValue = self::convertAssocArrayToObject($propType->getName(), $assocArray[$propName]);
            }

            $prop->setValue($classInstance, $propValue);
        }

        return $classInstance;
    }

    private static  function getArrayItemClassFromDocComment(?string $docComment): ?string
    {
        if (empty($docComment)) {
            return null;
        }

        if (preg_match('/@var\s+([^\[\]]+)\[\]/', $docComment, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private static function resolveBuildInTypeValue(mixed $dataValue, ReflectionProperty $currentProp): mixed
    {
        if (!is_array($dataValue)) {
            return $dataValue;
        }

        $docComment = $currentProp->getDocComment();
        $propertyClass = self::getArrayItemClassFromDocComment($docComment ? $docComment : null);

        if ($propertyClass === null || !class_exists($propertyClass)) {
            return $dataValue;
        }

        $items = [];
        foreach ($dataValue as $itemData) {
            $items[] = self::convertAssocArrayToObject($propertyClass, $itemData);
        }

        return $items;
    }
}
