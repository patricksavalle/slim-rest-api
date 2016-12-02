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

require_once BASE_PATH . '/Exception/InvalidFormatException.php';
require_once BASE_PATH . '/Exception/AuthorisationException.php';
require_once BASE_PATH . '/Exception/ResourceNotFoundException.php';
require_once BASE_PATH . '/Infra/Password.php';
require_once BASE_PATH . '/Infra/Db.php';
require_once BASE_PATH . '/Middleware/Authentication.php';

use SlimRestApi\Exception\AuthorisationException;
use SlimRestApi\Exception\ResourceNotFoundException;
use SlimRestApi\Infra\Db;
use SlimRestApi\Infra\Password;
use SlimRestApi\Middleware\Authentication;

class User
{
    static public function get(string $userid)
    {
        $getUser = function ($userid) {
            $stmt = Db::execute("SELECT * FROM member WHERE uaddress=:userid_1 OR email=:userid_2 OR lastname=:userid_3",
                [
                    ':userid_1' => $userid,
                    ':userid_2' => $userid,
                    ':userid_3' => $userid,
                ]);
            if ($stmt->rowCount() == 0) {
                throw new ResourceNotFoundException('User not found');
            }
            $user = $stmt->fetch();
            unset($user->password_hash);
            unset($user->id);
            return $user;
        };

        return $getUser($userid);
    }

    static public function patch(\stdClass $user)
    {
        assert(empty($user->email));
        assert(empty($user->password_hash));
        assert(empty($user->id));
        assert(empty($user->uaddress));
        if (Db::update('member', $user, ['id' => Authentication::user()->id])->rowCount() == 0) {
            throw new \Exception;
        }
        return true;
    }

    static public function activateEmail(string $email)
    {
        assert(filter_var($email, FILTER_VALIDATE_EMAIL) !== false);
        Db::transaction(function () use ($email) {
            $user = Db::execute("SELECT * FROM member WHERE email=:email", [':email' => $email])->fetch();
            if (empty($user->status)) {
                // user does not exist -> create new user and activate
                if (Db::execute("INSERT member(uaddress,email,status)VALUES(MD5(UUID()),:email,'activated')", [':email' => $email])->rowCount() == 0)
                    throw new \Exception;
            } elseif (in_array($user->status, ['suspended', 'deactivated'])) {
                // user was banned or deactivated --> ignore
                throw new AuthorisationException("Cannot activate account with status " . $user->status);
            } elseif (in_array($user->status, ['invited', 'registered'])) {
                // user needs to be activated
                if (Db::execute("UPDATE member SET status='activated' WHERE email=:email", [':email' => $email])->rowCount() == 0)
                    throw new \Exception;
            }
        });
    }

    static public function patchEmail(string $userid, string $new_email)
    {
        assert(filter_var($new_email, FILTER_VALIDATE_EMAIL) !== false);
        if (Db::update('member', ['email' => $new_email], ['uaddress' => $userid])->rowCount() == 0) {
            throw new \Exception;
        }
        return true;
    }

    static public function patchPassword(string $newpassword)
    {
        if (Db::update('member', ['password_hash' => Password::hash($newpassword)], ['id' => Authentication::user()->id])->rowCount() == 0) {
            throw new \Exception;
        }
        return true;
    }
}
