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

require_once BASE_PATH . '/Infra/Memcache.php';
require_once BASE_PATH . '/Middleware/Authentication.php';

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

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        $return = $next($request, $response);
        assert(strcasecmp($request->getMethod(), 'GET') == 0 and empty(Authentication::user()->id));
        Memcache::set($request->getUri(), $response->getBody(), $this->expiration);
        return $return;
    }
}
