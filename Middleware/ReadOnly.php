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

require_once BASE_PATH . '/Infra/Db.php';
require_once BASE_PATH . '/Middleware/Authentication.php';

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SlimRestApi\Infra\Db;

class ReadOnly
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        Db::exec("SET SESSION TRANSACTION READ ONLY");
        return $next($request, $response);
    }
}
