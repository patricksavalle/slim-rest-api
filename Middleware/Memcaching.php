<?php /** @noinspection PhpUnused */

declare(strict_types=1);

namespace SlimRestApi\Middleware;

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
            error_log( "memcache miss: ". $request->getUri());
            $return = $next($request, $response->withHeader("cache-control", "max-age=" . $this->expiration));
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
