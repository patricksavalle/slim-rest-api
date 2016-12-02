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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ExceptionHandling
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        try {

            return $next($request, $response);

        } catch (\Throwable $exception) {

            $status = $exception->getCode();
            if (!is_integer($status) or $status < 100 or $status > 599) {
                $status = 500;
            }
            if ($status >= 500) {
                error_log($exception->getMessage() . ' @ ' . $exception->getFile() . '(' . $exception->getLine() . ')');
            }
            return $response->withJson($exception->getMessage(), $status);
        }
    }
}
