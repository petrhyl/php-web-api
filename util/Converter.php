<?php

namespace Core\Util;

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

        return self::convertArrayToClass($classReflection, $assocArray);
    }

    private static function convertArrayToClass(ReflectionClass $classReflection, array $arr): object
    {
        $properties = $classReflection->getProperties();

        if (count($arr) > count($properties)) {
            throw new \Exception("Not able to deserialize array into type of " . $classReflection->getName());
        }

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

            if (
                !array_key_exists($propName, $arr)
                && (!$propType->allowsNull() || !$prop->hasDefaultValue())
            ) {
                throw new \Exception("Not able to deserialize array into type of " . $classInstance::class . ". Missing value for property $propName");
            }

            if ($propType->isBuiltin()) {
                $prop->setValue($classInstance, $arr[$propName]);
            } else {
                $propInstance = self::convertArrayToClass($prop->getDeclaringClass(), $arr[$propName]);
                $prop->setValue($classInstance, $propInstance);
            }
        }

        return $classInstance;
    }
}
