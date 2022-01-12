<?php /** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace SlimRestApi\Middleware;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SlimRestApi\Infra\Db;
use SlimRestApi\Infra\Password;

// TODO change to HTTP Bearer authentication

class Authentication
{
    static private $session;

    static public function getSessionTtl(): int
    {
        return 1;
    }

    static public function getSession()
    {
        // returns the hashed user identification stored with the token
        // can be used in overloaded method to retrieve user profile
        return self::$session;
    }

    // ---------------------------------------------------
    // Logout
    // ---------------------------------------------------

    static public function deleteSession()
    {
        Db::execute("DELETE FROM authentications23ghd94d WHERE token=:token", [":token" => self::$session->token]);
    }

    // ---------------------------------------------------
    // Creates a session token, do some bookkeeping
    // ---------------------------------------------------

    static public function createSession(string $userid): array
    {
        // to make this module self-contained we create our own table on demand
        // this gives some overhead every time a session is created but not very much
        // we will store a hash of the used userid
        if (Db::execute("CREATE TABLE IF NOT EXISTS authentications23ghd94d
            (
                token      CHAR(32) NOT NULL,
                lastupdate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                userid     CHAR(32) NOT NULL,
                PRIMARY KEY (token),
                INDEX (lastupdate)
            )")->rowCount() > 0) {
            error_log("Table authentications23ghd94d created on demand by " . __CLASS__);
        }

        // delete expired tokens
        Db::execute("DELETE FROM authentications23ghd94d WHERE lastupdate < SUBDATE(CURRENT_TIMESTAMP, INTERVAL :int HOUR)",
            [":int" => static::getSessionTtl()]);

        // create a new token
        $session_token = Password::randomMD5();
        if (Db::execute("INSERT INTO authentications23ghd94d(token,userid) VALUES (:token,MD5(:userid))",
                [
                    ":token" => $session_token,
                    ":userid" => $userid,
                ])->rowCount() == 0
        ) {
            throw new Exception;
        }
        // now logged in
        return ['X-Session-Token' => $session_token];
    }

    // -------------------------------------------------------------
    // The SLIM API middleware plugin, verifies the token.
    // Very efficient, only a single query.
    // -------------------------------------------------------------

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        // extract token from header
        $token = $request->getHeader('X-Session-Token')[0] ?? false;
        if ($token !== false) {
            // retrieve the token if still valid
            self::$session = Db::execute("SELECT * FROM authentications23ghd94d 
                WHERE token=:token AND lastupdate > SUBDATE(CURRENT_TIMESTAMP, INTERVAL :int HOUR)",
                [':token' => $token, ":int" => static::getSessionTtl()])->fetch();
            if (self::$session !== false) {
                // update the token
                Db::execute("UPDATE authentications23ghd94d SET lastupdate=CURRENT_TIMESTAMP WHERE token=:token", [':token' => $token]);
                // continue execution
                return $next($request, $response);
            }
        }
        // deny authorization
        return $response
            ->withJson("Invalid token in X-Session-Token header")
            ->withStatus(401)
            // we must return XBasic (not Basic) to prevent clients from opening the AUTH dialog
            ->withHeader('WWW-Authenticate', 'XBasic realm=api');
    }
}
