<?php /** @noinspection PhpUnused */
/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types = 1);

namespace SlimRestApi\Infra;

use ErrorException;
use Exception;
use stdClass;

abstract class Singleton extends stdClass
{
    static final public function __callStatic(string $method, array $arguments)
    {
        try {
            return call_user_func_array([self::instantiate(), $method], $arguments);
        } catch (Exception $e) {
            $trace = (object)$e->getTrace()[3];
            throw new ErrorException($e->getMessage(), 500, E_ERROR, $trace->file, $trace->line);
        }
    }

    static protected function setProperty(string $name, $value)
    {
        /** @noinspection PhpVariableVariableInspection */
        self::instantiate()->$name = $value;
    }

    static protected function getProperty(string $name)
    {
        /** @noinspection PhpVariableVariableInspection */
        return self::instantiate()->$name;
    }

    abstract protected static function instance();

    static private function instantiate()
    {
        if (static::$instance == null) {
            static::$instance = static::instance();
            assert(is_object(static::$instance));
        }
        return static::$instance;
    }

    private final function __clone()
    {
    }

}

