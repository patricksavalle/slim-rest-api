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

namespace SlimRestApi\Exception;

require_once 'Exception.php';

class JsonException extends Exception
{
    public function __construct(int $httpcode = 400)
    {
        switch (json_last_error()) {
            case JSON_ERROR_NONE :
                $msg = 'No JSON error';
                break;
            case JSON_ERROR_DEPTH :
                $msg = 'JSON syntax error, maximum stack depth exceeded';
                break;
            case JSON_ERROR_CTRL_CHAR :
                $msg = 'JSON syntax error, unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX :
                $msg = 'JSON syntax error, malformed JSON';
                break;
            default :
                $msg = 'JSON syntax error, invalid JSON';
                break;
        }
        parent::__construct($msg, $httpcode);
    }
}