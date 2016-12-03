<?php

declare(strict_types = 1);

namespace SlimRestApi\Infra;

class Ini
{
    static protected $ini_values = null;

    static public function get(string $name)
    {
        if (null === static::$ini_values) {
            static::$ini_values = parse_ini_file('slim-rest-api.ini', false, INI_SCANNER_TYPED);
        }
        return static::$ini_values[$name];
    }
}

