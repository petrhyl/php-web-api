<?php

namespace WebApiCore\Configuration;

use ReflectionClass;
use WebApiCore\Util\Constants;

class ConfigurationManager
{
    public const DEFAULT_CONFIG_FILE_PATH = '/config/settings/appSettings.php';
    public const DEFAULT_CONFIG_DATA_VARIABLE_NAME = 'confData';

    private ConfigurationSource $configurationSource;

    public function __construct(
        ?ConfigurationOptions $options = null
    ) {
        if ($options === null) {
            $options = new ConfigurationOptions();
            $options->configFileName = Constants::appParentDir() . self::DEFAULT_CONFIG_FILE_PATH;
            $options->configDataVariableName = self::DEFAULT_CONFIG_DATA_VARIABLE_NAME;
        }

        $this->configurationSource = new ConfigurationSource($options);
    }

    /**
     * @param string $className name of the class that will be configured with the configuration data from a section of the configuration data array given by the $configurationPath
     * @param string $configurationPath string that contains keys that will be used to get nested array from the configuration data array
     * * each kye in the $path has to be separated by {@see WebApiCore\Configuration\ConfigurationSource::CONFIGURATION_PATH_DELIMITER}
     * @return object instance of the class that was configured with the configuration data from the configuration path
     */
    public function configure(string $class, string $configurationPath): object
    {
        $sectionData = $this->configurationSource->getSectionData($configurationPath);

        if (empty($sectionData)) {
            return $this->buildConfiguration($class, $sectionData);
        }

        foreach ($sectionData as $key => $value) {
            if (is_array($value)) {
                $sectionData = $value;
                break;
            }
        }

        return $this->buildConfiguration($class, $sectionData);
    }

    /**     
     * @param string $configurationPath string that contains keys that will be used to get nested array from the configuration data array
     * * each key in the $path has to be separated by {@see WebApiCore\Configuration\ConfigurationSource::CONFIGURATION_PATH_DELIMITER}
     * @return array associative array which its top level array contains only single key that is the last key in the configuration path
     * * `value` of the top level key can be:
     * 1) an associative array of key-value pairs with the configuration data or 
     * 2) a single value or 
     * 3) an empty array if the path is empty or there is no configuration data
     */
    public function getSection(string $configurationPath): array
    {
        $sectionData = $this->configurationSource->getSectionData($configurationPath);

        return $sectionData;
    }

    private function buildConfiguration(string $class, array $data): object
    {
        $reflectionClass = new ReflectionClass($class);

        $instance = $reflectionClass->newInstanceWithoutConstructor();
        $properties = $reflectionClass->getProperties();

        foreach ($properties as $property) {
            $propertyName = $property->getName();

            if (!array_key_exists($propertyName, $data)) {
                continue;
            }

            $propertyType = $property->getType();

            $dataValue = $data[$propertyName];

            if (!$propertyType->isBuiltin()) {
                if (is_array($dataValue)) {
                    $propertyValue = $this->buildConfiguration($propertyType->getName(), $dataValue);
                } else {
                    continue;
                }
            }

            $propertyValue = $dataValue;

            $property->setAccessible(true);
            $property->setValue($instance, $propertyValue);
        }

        return $instance;
    }
}
