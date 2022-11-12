<?php /** @noinspection ALL */

declare(strict_types=1);

namespace SlimRestApi\Infra;

use Firehed\SimpleLogger\Stderr;
use Firehed\SimpleLogger\Stdout;
use Firehed\SimpleLogger\Syslog;
use Psr\Log\LoggerInterface;

class Log extends Singleton
{
    static $instance;
    static protected function instance(): LoggerInterface
    {
            return match (Ini::get("logger_type")) {
                "stderr" => new Stderr,
                "stdout" => new Stdout,
                "syslog" => new Syslog,
            };
    }
}
