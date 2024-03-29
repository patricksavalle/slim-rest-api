<?php

declare(strict_types=1);

namespace SlimRestApi\Infra;

use Exception;
use InvalidArgumentException;

class Ini
{
    static public function get(string $name): mixed
    {
        static $iniValues = [];

        if (empty($iniValues)) {
            $iniValues = parse_ini_file('slim-rest-api.ini', false, INI_SCANNER_TYPED);
        }
        try {
            return $iniValues[$name];
        } catch (Exception $e) {
            throw new InvalidArgumentException("Key '$name' not found in slim-rest-api.ini" );
        }
    }
}
