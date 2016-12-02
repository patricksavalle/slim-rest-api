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

namespace SlimRestApi\Middleware;

require_once BASE_PATH . '/Exception/AuthenticationException.php';
require_once BASE_PATH . '/Infra/Db.php';
require_once BASE_PATH . '/Infra/Ini.php';
require_once BASE_PATH . '/Infra/Password.php';

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SlimRestApi\Exception\AuthenticationException;
use SlimRestApi\Infra\Db;
use SlimRestApi\Infra\Password;

class Authentication
{
    const ALLOW_ANONYMOUS = true;
    static protected $authenticated_user = null;
    static protected $session_token = null;
    static protected $password = null;
    private $allow_anonymous = false;

    public function __construct(bool $allow_anonymous = false)
    {
        $this->allow_anonymous = $allow_anonymous;
    }

    static public function user(): \stdClass
    {
        return (object)static::$authenticated_user;
    }

    static public function token()
    {
        return static::$session_token;
    }

    static public function password()
    {
        return static::$password;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        if (isset($request->getHeader('PHP_AUTH_USER')[0])
            and isset($request->getHeader('PHP_AUTH_PW')[0])
            and isset($request->getHeader('X-Session-Token')[0])
        ) {
            throw new AuthenticationException('SESSION AUTH and BASIC AUTH cannot be used together', 400);
        }

        if (isset($request->getHeader('X-Session-Token')[0])
            and $this->tokenAuthentication($request->getHeader('X-Session-Token')[0])
        ) {
            return $next($request, $response);
        }

        if (isset($request->getHeader('PHP_AUTH_USER')[0])
            and isset($request->getHeader('PHP_AUTH_PW')[0])
            and $this->basicAuthentication($request->getHeader('PHP_AUTH_USER')[0], $request->getHeader('PHP_AUTH_PW')[0])
        ) {
            return $next($request, $response);
        }

        if ($this->allow_anonymous) {
            return $next($request, $response);
        }

        return $response
            ->withJson("Invalid username and/or password")
            ->withStatus(401)
            // we must return XBasic (not Basic) to prevent clients from opening the AUTH dialog
            ->withHeader('WWW-Authenticate', 'XBasic realm=api');
    }

    private function basicAuthentication(string $user, string $password)
    {
        $user = Db::execute("SELECT * FROM member WHERE email=:username AND status IN ('activated','admin')", [":username" => $user])->fetch();
        if (!(isset($user->password_hash) and Password::check($password, $user->password_hash))) {
            sleep(5);
            return false;
        }
        static::$authenticated_user = $user;
        static::$password = $password;
        return true;
    }

    private function tokenAuthentication(string $token)
    {
        // check for the session token, a database event deletes timed-out sessions
        $user = Db::execute("SELECT member.*
            FROM sessiontoken AS session
            JOIN member ON member.id=session.userid
            WHERE session.token=:token AND member.status in ('activated','admin')",
            [
                ':token' => $token,
            ])->fetch();
        if (!$user) {
            return false;
        }

        Db::execute("UPDATE sessiontoken SET lastupdate=NOW() WHERE token=:token", [':token' => $token]);

        static::$authenticated_user = $user;
        static::$session_token = $token;
        return true;
    }

}
