<?php /** @noinspection PhpUndefinedMethodInspection */

declare(strict_types=1);

namespace SlimRestApi\Middleware {

    use Ahc\Jwt\JWT;
    use Ahc\Jwt\JWTException;
    use Exception;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use SlimRestApi\Infra\Ini;
    use SlimRestApi\Infra\Log;
    use SlimRestApi\Infra\Password;

    class AuthenticateJWT
    {
        private bool $throwexception;
        static protected array $payload;

        // ---------------------------------------------------------------------------------------
        // Create a JWT object, initializes and store the secret in apcu
        // ---------------------------------------------------------------------------------------

        /**
         * @throws Exception
         */
        static private function createJwt(): JWT
        {
            // this is not a password, just a key for apcu value (md5("jwt_secret"))
            static $key = "9182e15a2164dd4c3f538f2becdf3a1f";
            $secret = apcu_fetch($key);
            if (empty($secret)) {
                // fresh server start, must generate a secret
                apcu_add($key, password::randomMD5());
                // get key from apcu
                $secret = apcu_fetch($key);
                if (empty($secret)) {
                    throw new Exception("critical apcu error, could not add/fetch key");
                }
            }
            return new JWT($secret, 'HS256', Ini::get("jwt_maxage") );
        }

        public static function getPayload(): array
        {
            return self::$payload;
        }

        // ----------------------------------------------
        // 2FA action, creates a session token.
        // Decorates the parent class method.
        // ----------------------------------------------

        /**
         * @throws Exception
         */
        static public function createSession(array $payload): array
        {
            return ['X-Session-Token' => self::createJwt()->encode($payload)];
        }

        public function __construct(bool $throwexception = true)
        {
            $this->throwexception = $throwexception;
        }

        /**
         * @throws Exception
         */
        public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
        {
            try {
                try {
                    // extract token from header
                    $value = $request->getHeader('Authorization')[0] ?? false;
                    if (empty($value)) {
                        Log::debug("Authorization header not present");
                        throw new JWTException;
                    }
                    $jwt = substr($value, strlen("Bearer "));
                    if (empty($jwt)) {
                        Log::debug("Authorization header invalid");
                        throw new JWTException;
                    }
                    // check the JWT
                    assert(!empty(preg_match("@^[A-Za-z0-9-_=]+\.[A-Za-z0-9-_=]+\.?[A-Za-z0-9-_.+/=]*$@", $jwt)));
                    static::$payload = (self::createJwt())->decode($jwt)["payload"];
                    // return a new token
                    $response = $response->withHeader("X-Session-Token", (self::createJwt())->encode(["channelid" => static::$payload]));

                } catch (JWTException) {
                    if ($this->throwexception) {
                        throw new JWTException;
                    }
                }
                return $next($request, $response);

            } catch (JWTException) {
                // deny authorization
                return $response
                    ->withJson("Invalid token in Authorization bearer header")
                    ->withStatus(401);
            }
        }
    }
}