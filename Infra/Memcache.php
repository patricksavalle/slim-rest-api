<?php /** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types = 1);

namespace {

    if (!class_exists("Memcached")) {

        /** @noinspection PhpMultipleClassesDeclarationsInOneFile */

        /** @noinspection PhpUndefinedClassInspection */
        class Memcached
        {
            public function addServer($host, $port)
            {
                assert($host !== null and $port !== null);
                error_log("class Memcached not found, dummy used");
            }

            public function get($key)
            {
                assert($key !== null);
                return false;
            }

            public function set($key, $value, $expiration)
            {
                assert($key !== null and $value !== null and $expiration !== null);
                return true;
            }

            /** @noinspection PhpUnused */
            public function getResultCode()
            {
                return 1;
            }
        }

    }
}

namespace SlimRestApi\Infra {

    use Exception;
    use Memcached;

    /** @noinspection PhpMultipleClassesDeclarationsInOneFile */
    final class Memcache extends Singleton
    {
        static protected $instance = null;

        final static public function call_user_func_array(callable $function, array $param_arr, int $expiration = 0)
        {
            if (!is_callable($function, false, $method_name)) {
                throw new Exception("Invalid function call" . print_r($function, true));
            }
            // Don't allow anonymous functions
            assert($method_name != 'Closure::__invoke');
            // Mangle function signature and try to get from cache
            $cache_key = hash('md5', $method_name . serialize($param_arr));
            $result = static::get($cache_key);
            if ($result === false) {
                // if not, call the function and cache result
                $result = call_user_func_array($function, $param_arr);
                if (static::set($cache_key, $result, $expiration) === false) {
                    $error_code = static::getResultCode();
                    error_log("Memcached error ($error_code) on method: $method_name");
                }
            }
            return $result;
        }

        static protected function instance(): Memcached
        {
            $mc = new Memcached();
            $mc->addServer(Ini::get('memcache_host'), Ini::get('memcache_port'));
            return $mc;
        }
    }
}