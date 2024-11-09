<?php

namespace WebApiCore\Util;

use ReflectionClass;

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
            if (!$prop->isPublic()) {
                continue;
            }

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

            if ($propType->isBuiltin()) {
                if (is_array($assocArray[$propName])) {
                    $docComment = $prop->getDocComment();
                    $propertyClass = self::getArrayItemClassFromDocComment($docComment ? $docComment : null);

                    if ($propertyClass !== null) {
                        if (class_exists($propertyClass)) {
                            $items = [];

                            foreach ($assocArray[$propName] as $itemData) {
                                $items[] = self::convertAssocArrayToObject($propertyClass, $itemData);
                            }

                            $classInstance->$propName = $items;

                            continue;
                        }
                    }
                }

                $prop->setValue($classInstance, $assocArray[$propName]);
            } else {
                $propInstance = self::convertAssocArrayToObject($propType->getName(), $assocArray[$propName]);
                $prop->setValue($classInstance, $propInstance);
            }
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
}
