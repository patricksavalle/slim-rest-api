<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
/** @noinspection PhpUnnecessaryStaticReferenceInspection */
/** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types = 1);

namespace SlimRestApi\Infra {

    use Exception;

    final class Memcache
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
            $result = apcu_fetch($cache_key);
            if ($result === false) {
                // if not, call the function and cache result
                $result = call_user_func_array($function, $param_arr);
                if (apcu_add($cache_key, $result, $expiration) === false) {
                    error_log("APCu error on method: $method_name");
                }
            }
            return $result;
        }
    }
}