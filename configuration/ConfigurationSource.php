<?php

namespace WebApiCore\Configuration;

class ConfigurationSource
{
    public const CONFIGURATION_PATH_DELIMITER = '.';

    private ?array $configurationData = null;


    public function __construct(
        private ConfigurationOptions $options,
    ) {
        $this->loadConfigurationData();
    }

    /**
     * @param string $className name of the class that will be configured with the configuration data from a section of the configuration data array given by the $pathKeys
     * @param string $path string that contains keys that will be used to get nested array from the configuration data array
     * * each key in the $path has to be separated by a dot
     * @return array associative array which its top level array contains only single key that is the last key in the configuration path
     * * `value` of the top level key can be:
     * 1) an associative array of key-value pairs with the configuration data or 
     * 2) a single value or 
     * 3) an empty array if the path is empty or there is no configuration data
     */
    public function getSectionData(string $path): array
    {
        if (empty($this->configurationData)) {
            return [];
        }

        $pathKeys = explode(self::CONFIGURATION_PATH_DELIMITER, $path);
        $currentData = $this->configurationData;

        if (empty($pathKeys)) {
            return [];
        }

        $lastKey = '';

        foreach ($pathKeys as $key) {
            if (!array_key_exists($key, $currentData)) {
                return [$key => []];
            }


            $section = $currentData[$key];

            if (!is_array($section)) {
                return [$key => $section];
            }

            $currentData = $section;
            $lastKey = $key;
        }

        return [$lastKey => $currentData];
    }

    function getLastSectionKey(string $path): string
    {
        $pathKeys = explode(self::CONFIGURATION_PATH_DELIMITER, $path);

        if (empty($pathKeys)) {
            return '';
        }

        return $pathKeys[count($pathKeys) - 1];
    }

    private function loadConfigurationData(): void
    {
        if ($this->configurationData !== null) {
            return;
        }

        if (!is_file($this->options->configFileName)) {
            return;
        }

        if (!is_readable($this->options->configFileName)) {
            return;
        }

        if (pathinfo($this->options->configFileName, PATHINFO_EXTENSION) !== 'php') {
            return;
        }

        require_once($this->options->configFileName);

        $ooV_R_D_S = $this->options->configDataVariableName;

        if (!isset($$ooV_R_D_S) || empty($$ooV_R_D_S) || !is_array($$ooV_R_D_S)) {
            return;
        }

        $this->configurationData = $$ooV_R_D_S;
    }
}
