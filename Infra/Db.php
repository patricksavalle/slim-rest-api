<?php /** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUndefinedMethodInspection */

declare(strict_types=1);

namespace SlimRestApi\Infra;

use DateTime;
use DateTimeZone;
use ErrorException;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use RangeException;
use Throwable;

/**
 * Class Db, implements some util and config on top of PDO
 * This class allows for very simpel syntax:
 *      e.g. Db::prepare('');
 * See the Transactionize function for an example of that syntax.
 */
class Db extends Singleton
{
    static protected $instance = null;
    static protected $statements = [];
    static protected $logqueries = false;

    /** @noinspection PhpUnhandledExceptionInspection */
    static public function transaction(callable $callable)
    {
        if (static::inTransaction()) {
            return call_user_func($callable);
        }
        static::beginTransaction();
        try {
            $result = call_user_func($callable);
            static::commit();
            return $result;
        } catch (Throwable $e) {
            static::rollback();
            throw $e;
        }
    }

    /** @noinspection PhpUnhandledExceptionInspection */
    static public function execute(string $query, array $params = []): PDOStatement
    {
        static $query_logging = -1;
        if ($query_logging === -1) {
            $query_logging = Ini::get("database_query_logging");
        }
        try {
            $timems = microtime(true);
            $md5 = md5($query);
            // check if we already prepared this query
            if (empty(self::$statements[$md5])) {
                self::$statements[$md5] = static::prepare($query);
            }
            self::$statements[$md5]->execute($params);
            if ($query_logging) error_log("[" . round((microtime(true) - $timems) * 1000) . "ms]: " . $query);
            return self::$statements[$md5];

        } catch (Throwable $e) {
            error_log($query);
            throw $e;
        }
    }

    static public function fetchAll(string $query, array $params = [], int $cachettl = 0)
    {
        if ($cachettl === 0) {
            return self::execute($query, $params)->fetchAll();
        }
        $cache_key = hash('md5', $query . serialize($params));
        $result = apcu_fetch($cache_key);
        if ($result === false) {
            $result = self::execute($query, $params)->fetchAll();
            if ($result !== false) {
                apcu_add($cache_key, $result, $cachettl);
            }
        }
        return $result;
    }

    static public function fetch(string $query, array $params = [])
    {
        return self::execute($query, $params)->fetch();
    }

    /** @noinspection PhpUnhandledExceptionInspection */
    static public function date(string $datetime, string $timezone = 'UTC'): string
    {
        // normalise date to UTC and MySQL-format
        return (new DateTime($datetime, new DateTimeZone($timezone)))
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s');
    }

    static public function update(string $table, $data, array $primkeyvalue): PDOStatement
    {
        assert(count($primkeyvalue) == 1);
        assert(ctype_alnum($table));

        // add the primary key
        $primvalue = reset($primkeyvalue);
        $primkey = key($primkeyvalue);
        $values = [":" . $primkey => $primvalue];

        // add rest of fields
        $fields = [];
        foreach ($data as $k => $v) {
            assert(preg_match("/^[^\d\W]\w*$/", $k) > 0);
            $values[":" . $k] = $v;
            $fields[] = $k . '=:' . $k;
        }
        if (empty($fields)) {
            throw new RangeException('Empty update');
        }
        $fields = implode(',', $fields);
        return static::execute("UPDATE $table SET $fields WHERE $primkey=:$primkey", $values);
    }

    /** @noinspection PhpUnused */
    static public function insert(string $table, $data): PDOStatement
    {
        assert(ctype_alnum($table));
        $fields = [];
        $values = [];
        foreach ($data as $k => $v) {
            assert(preg_match("/^[^\d\W]\w*$/", $k) > 0);
            $values[":" . $k] = $v;
            $fields[] = $k . '=:' . $k;
        }
        if (empty($fields)) {
            throw new InvalidArgumentException('Empty insert');
        }
        $fields = implode(',', $fields);
        $placeholders = implode(',', array_keys($values));
        return static::execute("INSERT INTO $table($fields)VALUES($placeholders)", $values);
    }

    static protected function instance(): PDO
    {
        self::$logqueries = Ini::get('database_query_logging');
        $dbhost = Ini::get('database_host');
        $dbname = Ini::get('database_name');
        $dbcharset = Ini::get('database_charset');
        $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=$dbcharset", Ini::get('database_user'), Ini::get('database_password'));
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // otherwise binding int-parameters will fail
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        return $pdo;
    }

}

