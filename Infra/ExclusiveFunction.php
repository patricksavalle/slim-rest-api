<?php

declare(strict_types=1);

namespace SlimRestApi\Infra {

    use Exception;

    class ExclusiveFunction
    {
        /**
         * @throws Exception
         */
        public function __invoke(callable $function, array $param_arr = [], int $max_lock_time = 60)
        {
            if (!is_callable($function, false, $method_name)) {
                throw new Exception("Uncallable function: " . $method_name, 500);
            }
            if ($method_name === 'Closure::__invoke') {
                throw new Exception("Uncallable function: " . $method_name, 500);
            }
            $cache_key = hash('md5', $method_name . serialize($param_arr));
            if (apcu_add($cache_key, $cache_key, $max_lock_time) !== true) {
                throw new Exception("Locked, wait for completion", 409);
            }
            try {
                return call_user_func_array($function, $param_arr);
            } finally {
                apcu_delete($cache_key);
            }
        }
    }
}