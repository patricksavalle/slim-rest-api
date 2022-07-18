<?php /** @noinspection PhpMultipleClassDeclarationsInspection */
/** @noinspection PhpUnnecessaryStaticReferenceInspection */
/** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace SlimRestApi\Infra {

    use Exception;

    final class Memcache
    {
        static protected $instance = null;

        final static public function call_user_func_array(callable $function, array $param_arr, int $expiration = 0)
        {
            $method_name = null;
            if (!is_callable($function, false, $method_name)) {
                throw new Exception("Uncallable function: " . $method_name, 500);
            }
            if ($method_name === 'Closure::__invoke') {
                throw new Exception("Uncallable function: " . $method_name, 500);
            }
            // Mangle function signature and try to get from cache
            $cache_key = hash('md5', $method_name . serialize($param_arr));
            $result = apcu_fetch($cache_key);
            if ($result === false) {
                // if not, call the function and cache result
                $result = call_user_func_array($function, $param_arr);
                apcu_add($cache_key, $result, $expiration);
            }
            return $result;
        }
    }
}