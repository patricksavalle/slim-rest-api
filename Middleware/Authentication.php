<?php /** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace SlimRestApi\Middleware;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SlimRestApi\Infra\Db;
use SlimRestApi\Infra\Password;
use stdClass;

// TODO change to HTTP Bearer authentication

class Authentication
{
    static protected $session_token;

    static public function getSessionTtl() : int
    {
        return 1;
    }

    static public function getSession(): stdClass
    {
        // returns the hashed user identification stored with the token
        // can be used in overloaded method to retrieve user profile
        return Db::execute("SELECT * FROM authentications23ghd94d WHERE token=:token", [":token" => self::$session_token])->fetch();
    }

    // ---------------------------------------------------
    // Logout
    // ---------------------------------------------------

    static public function deleteSession()
    {
        Db::execute("DELETE FROM authentications23ghd94d WHERE token=:token", [":token" => self::$session_token]);
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
        self::$session_token = Password::randomMD5();
        if (Db::execute("INSERT INTO authentications23ghd94d(token,userid) VALUES (:token,MD5(:userid))",
                [
                    ":token" => self::$session_token,
                    ":userid" => $userid,
                ])->rowCount() == 0
        ) {
            throw new Exception;
        }
        // now logged in
        return ['X-Session-Token' => self::$session_token];
    }

    // -------------------------------------------------------------
    // The SLIM API middleware plugin, verifies the token.
    // Very efficient, only a single query.
    // -------------------------------------------------------------

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        $tokenAuthenticated = function (string $token): bool {
            // authenticate and actualize the token if still valid
            return Db::execute("UPDATE authentications23ghd94d SET lastupdate=NOW() 
                    WHERE token=:token AND lastupdate > SUBDATE(CURRENT_TIMESTAMP, INTERVAL :int HOUR)",
                    [':token' => $token, ":int" => static::getSessionTtl()])->rowCount() === 1;
        };

        self::$session_token = $request->getHeader('X-Session-Token')[0] ?? null;
        if (isset(self::$session_token) and $tokenAuthenticated(self::$session_token)
        ) {
            return $next($request, $response);
        }
        return $response
            ->withJson("Invalid token in X-Session-Token header")
            ->withStatus(401)
            // we must return XBasic (not Basic) to prevent clients from opening the AUTH dialog
            ->withHeader('WWW-Authenticate', 'XBasic realm=api');
    }
}
