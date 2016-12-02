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

namespace SlimRestApi\Method;

require_once BASE_PATH . '/Infra/Db.php';

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Cronjob
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, \stdClass $args)
    {
        // must be defined by sub-class
        return $this->{$args->period}();
    }
}
