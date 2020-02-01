<?php /** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUndefinedMethodInspection */

declare(strict_types = 1);

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
        try {

            $md5 = md5($query);
            // check if we already prepared this query
            if (empty(self::$statements[$md5])) {
                self::$statements[$md5] = static::prepare($query);
            }

            self::$statements[$md5]->execute($params);
            return self::$statements[$md5];

        } catch (PDOException $e) {

            if (stripos($e->getMessage(), 'Duplicate') !== false) {
                throw new ErrorException('Duplicate key/name', 400, E_ERROR, $e->getFile(), $e->getLine());
            }
            throw $e;
        }
    }

    /** @noinspection PhpUnhandledExceptionInspection */
    static public function date(string $datetime, string $timezone = 'UTC'): string
    {
        // there are some timezone bugs in PHP, just translate
//        switch ($timezone) {
//            case 'Asia/Yangon' :
//                error_log("Asia/Yangon converted to MMT, check if newest PHP already implements this timezone");
//                $timezone = 'MMT'; // Myanmar Time
//                // bug report: https://bugs.php.net/bug.php?id=73467
//                break;
//        }
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
        return static::execute("UPDATE {$table} SET {$fields} WHERE {$primkey}=:{$primkey}", $values);
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
        return static::execute("INSERT INTO {$table}({$fields})VALUES({$placeholders})", $values);
    }

    static protected function instance(): PDO
    {
        $dbhost = Ini::get('database_host');
        $dbname = Ini::get('database_name');
        $dbcharset = Ini::get('database_charset');
        $pdo = new PDO("mysql:host={$dbhost};dbname={$dbname};charset={$dbcharset}", Ini::get('database_user'), Ini::get('database_password'));
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); // otherwise binding int-parameters will fail
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        return $pdo;
    }

}

