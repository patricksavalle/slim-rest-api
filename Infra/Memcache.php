<?php

/**
 * TRIPDRIVE.COM
 *
 * @link:       api.tripdrive.com
 * @copyright:  VCK TRAVEL BV, 2016
 * @author:     patrick@patricksavalle.com
 *
 * Note: use coding standards at http://www.php-fig.org/psr/
 */

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

            public function getResultCode()
            {
                return 1;
            }
        }

    }
}

namespace SlimRestApi\Infra {

    require_once BASE_PATH . '/Infra/Ini.php';
    require_once BASE_PATH . '/Infra/Singleton.php';

    /** @noinspection PhpMultipleClassesDeclarationsInOneFile */
    final class Memcache extends Singleton
    {
        static protected $instance = null;

        static protected function instance(): \Memcached
        {
            $mc = new \Memcached();
            $mc->addServer(Ini::get('memcache_host'), Ini::get('memcache_port'));
            return $mc;
        }
    }
}