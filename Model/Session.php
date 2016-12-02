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

namespace SlimRestApi\Model;

require_once BASE_PATH . '/Infra/Db.php';
require_once BASE_PATH . '/Infra/Password.php';
require_once BASE_PATH . '/Middleware/Authentication.php';

use SlimRestApi\Infra\Db;
use SlimRestApi\Infra\Password;
use SlimRestApi\Middleware\Authentication;

class Session
{
    static public function post(string $email = null)
    {
        if (null === $email) {
            $email = Authentication::user()->email;
        }
        $token = Password::randomMD5();
        if (Db::execute("INSERT INTO sessiontoken(token,userid)
            SELECT :token, id FROM member WHERE email=:email",
                [
                    ":token" => $token,
                    ":email" => $email,
                ])->rowCount() == 0
        ) {
            throw new \Exception;
        }
        return ['X-Session-Token' => $token];
    }

    static public function delete()
    {
        if (Db::execute("DELETE FROM sessiontoken WHERE token=:token",
                [":token" => Authentication::token()])
                ->rowCount() == 0
        ) {
            throw new \Exception;
        }
        return true;
    }

}