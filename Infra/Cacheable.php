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

namespace SlimRestApi\Infra;

require_once BASE_PATH . '/Infra/Memcache.php';

abstract class Cacheable extends \stdClass
{
    public function __construct()
    {
        // Assume this ctor is called as last statement in subclass ctor
        // Mangle object state and try to get from cache
        $cache_key = hash('md5', serialize($this));
        $result = Memcache::get($cache_key);
        if ($result === false) {
            // if not, call the function and cache result
            $result = $this();
            if (Memcache::set($cache_key, $result, static::$expiration) === false) {
                $error_code = Memcache::getResultCode();
                error_log("Memcached error ($error_code) on object: " . get_class($this));
            }
        }
        foreach ($result as $k => $v) {
            // enumerate result into dynamic variables
            /** @noinspection PhpVariableVariableInspection */
            $this->$k = $v;
        }
    }

    abstract public function __invoke();
}
