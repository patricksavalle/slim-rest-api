<?php /** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace SlimRestApi\Middleware {

    use DateInterval;
    use DateTime;
    use DateTimeInterface;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use SlimRestApi\Infra\Memcache;

    class Memcaching
    {
        protected $expiration;

        public function __construct(int $expiration = 60)
        {
            $this->expiration = $expiration;
        }

        /** @noinspection PhpUndefinedMethodInspection */
        public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
        {
            assert($request->getMethod() === 'GET');

            $cache = Memcache::get($request->getUri());
            if ($cache === false) {
                $return = $next($request,
                    $response
                        ->withHeader("cache-control", "public, max-age=" . $this->expiration)
                        ->withHeader("last-modified", (new DateTime())->format(DateTimeInterface::RFC1123))
                        ->withHeader("expires", (new DateTime())->add(new DateInterval("PT" . $this->expiration . "S"))->format(DateTimeInterface::RFC1123))
                );
                Memcache::set($request->getUri(), $response->getBody(), $this->expiration);
            } else {
                // -------------------------------------------------------------------------------------------
                // if the reverse proxy is installed correctly it will have served the cached response already
                // and this won't hit, otherwise we can still serve it from this server
                // -------------------------------------------------------------------------------------------
                $return = $response->withJson($cache);
            }
            return $return;
        }
    }
}