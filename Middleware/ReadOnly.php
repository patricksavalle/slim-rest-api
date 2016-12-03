<?php

declare(strict_types = 1);

namespace SlimRestApi\Middleware;

require_once '../Infra/Db.php';

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
