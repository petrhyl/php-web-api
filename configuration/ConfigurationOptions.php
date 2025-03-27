<?php

namespace WebApiCore\Configuration;

class ConfigurationOptions
{
    /**
     * @param string $configFileName has to be a full path to the php file
     */
    public string $configFileName;

    /**
     * @var string $configDataVariableName name of the variable in the configuration file
     * * containing all configuration data as an associative array of nested arrays or values
     * * each nested array's values can be used as a values of configuration class's properties
     */
    public string $configDataVariableName;
}
