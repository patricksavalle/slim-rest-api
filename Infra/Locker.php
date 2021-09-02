<?php /** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace SlimRestApi\Infra {

    use Exception;

    class Locker
    {
        /**
         * Store a json return a secret to this json,
         * can be used for many things among which a
         * login session, one-time login codes etc.
         */
        static public function stash($iterable, int $ttl = 10 * 60): string
        {
            // to make this module self-contained we create our own DB on demand
            // this gives some overhead every time a token is stored but not very much
            Db::execute("CREATE TABLE IF NOT EXISTS tokens85df2
            (
                hash               CHAR(32)  NOT NULL,
                json               JSON      NOT NULL,
                expirationdatetime TIMESTAMP NOT NULL,
                PRIMARY KEY (hash),
                INDEX ( expirationdatetime )
            ) ENGINE = MYISAM");

            $json = json_encode($iterable);
            if (empty($json)) {
                throw new Exception;
            }
            $hash = Password::randomMD5();
            if (Db::execute("INSERT INTO tokens85df2(hash, json, expirationdatetime) VALUES (:hash, :json, ADDDATE( NOW(), INTERVAL :ttl SECOND))",
                    [
                        ":hash" => $hash,
                        ":json" => $json,
                        ":ttl" => $ttl,
                    ])->rowCount() == 0
            ) {
                throw new Exception;
            }
            return $hash;
        }

        static public function unstash(string $hash)/* : mixed */
        {
            // try to get the token from the database
            $row = Db::execute("SELECT json FROM tokens85df2 WHERE hash = :hash AND NOW() < expirationdatetime", [":hash" => $hash])->fetch();
            if (empty($row)) {
                throw new Exception("Invalid or expired token or link", 401);
            }
            // remove the token from the database and remove expired tokens
            if (Db::execute("DELETE FROM tokens85df2 WHERE hash = :hash OR NOW() > expirationdatetime", [":hash" => $hash])->rowCount() == 0) {
                throw new Exception;
            }
            return json_decode($row->json);
        }
    }
}