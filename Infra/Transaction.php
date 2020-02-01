<?php /** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUnused */

declare(strict_types = 1);

namespace SlimRestApi\Infra;

use stdClass;

/**
 * Dynamic transaction.
 *
 * A transaction can be built from parts. Parts must be
 * callables that accept a single argument. The argument
 * is $this. The parts can use is to set and get dynamic
 * properties, see: http://krisjordan.com/dynamic-properties-in-php-with-stdclass
 *
 * In other words: the transaction object may/should contain all
 * intermediate results as constructed by the parts.
 *
 * Parts can be chained together and will be executed within a transaction
 *
 * At completion of the transaction the dynamic property 'result'
 * will be returned.
 *
 * Example usage:
 *
 *  (new Transaction)
 *      ->action(function($transaction){...})
 *      ->action(function($transaction){...})
 *      ->action( new InvokableClass(SomeParameter))
 *      ->execute();
 */
class Transaction extends stdClass
{
    protected $parts = [];

    public function action(callable $function): Transaction
    {
        $this->parts[] = $function;
        return $this;
    }

    public function set(stdClass $value): Transaction
    {
        foreach ($value as $k => $v) {
            /** @noinspection PhpVariableVariableInspection */
            $this->$k = $v;
        }
        return $this;
    }

    public function execute(): Transaction
    {
        return Db::transaction(function () {
            foreach ($this->parts as $part) {
                // pass $this so callables can exchange dynamic properties set on this object.
                $part($this);
            }
            // check for the special dynamic property
            return $this;
        });
    }
}

