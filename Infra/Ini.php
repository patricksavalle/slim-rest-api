<?php

declare(strict_types = 1);

namespace SlimRestApi\Infra;

class Ini
{
    static protected $iniValues = null;

    static public function get(string $name)
    {
        if (null === static::$iniValues) {
            static::$iniValues = parse_ini_file('slim-rest-api.ini', false, INI_SCANNER_TYPED);
        }
        return static::$iniValues[$name];
    }
}

