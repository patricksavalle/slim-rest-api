<?php

declare(strict_types = 1);

namespace SlimRestApi\Infra {

    class MemcachedFunction
    {
        public function __invoke(callable $function, array $param_arr = [], int $expiration = 60)
        {
            /** @noinspection PhpUnhandledExceptionInspection */
            return Memcache::call_user_func_array($function, $param_arr, $expiration);
        }
    }
}