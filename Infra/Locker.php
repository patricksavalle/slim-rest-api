<?php /** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace SlimRestApi\Infra {

    use Exception;

    class Locker
    {
        /**
         * Store a json return a secret to this json,
         * can be used for many things among which a
         * login session, one-time login codes etc.
         */
        static public function stash($iterable, int $ttl = 10 * 60): string
        {
            $json = json_encode($iterable);
            if (empty($json)) {
                throw new Exception;
            }
            $hash = Password::randomMD5();
            if (apcu_add($hash, $json, $ttl)===false) {
                throw new Exception("APCU failure! Low on memory?");
            }
            return $hash;
        }

        static public function unstash(string $hash)/* : mixed */
        {
            $json = apcu_fetch($hash);
            if (empty($json)) {
                throw new Exception("Invalid or expired token or link", 401);
            }
            return json_decode($json);
        }
    }
}