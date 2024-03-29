<?php

declare(strict_types = 1);

namespace SlimRestApi\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SlimRestApi\Infra\Db;

class ReadWrite
{
    /** @noinspection PhpUndefinedMethodInspection */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        Db::exec("SET SESSION TRANSACTION READ WRITE");
        return $next($request, $response);
    }
}
