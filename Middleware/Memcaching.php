<?php /** @noinspection PhpUnused */

declare(strict_types = 1);

namespace SlimRestApi\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SlimRestApi\Infra\Memcache;

class Memcaching
{
    protected $expiration;

    public function __construct(int $expiration = 60 * 60)
    {
        $this->expiration = $expiration;
    }

    /** @noinspection PhpUndefinedMethodInspection */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        $return = $next($request, $response);
        Memcache::set($request->getUri(), $response->getBody(), $this->expiration);
        return $return;
    }
}
