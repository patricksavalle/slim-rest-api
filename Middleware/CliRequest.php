<?php

declare(strict_types = 1);

namespace SlimRestApi\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CliRequest
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        if (PHP_SAPI !== 'cli') {
            return $response->withStatus(404);
        }
        return $next($request, $response);
    }

}
