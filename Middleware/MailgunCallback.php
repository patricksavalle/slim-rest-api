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

require_once BASE_PATH . '/Exception/AuthenticationException.php';
require_once BASE_PATH . '/Infra/Ini.php';

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SlimRestApi\Exception\AuthenticationException;
use SlimRestApi\Infra\Ini;

class MailgunCallback
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        // see: https://documentation.mailgun.com/user_manual.html#securing-webhooks
        // and: http://php.net/manual/en/function.hash-hmac.php
        // and: https://github.com/mailgun/mailgun-php/blob/master/src/Mailgun/Mailgun.php#L75
        $args = (object)$_POST;
        if ((time() - $args->timestamp) < 15) {
            $hash = hash_hmac("sha256", $args->timestamp . $args->token, Ini::get('mailgun_api_key'));
            if (hash_equals($hash, $args->signature)) {
                return $next($request, $response);
            }
        }
        error_log('Illegal callback:' . print_r($_REQUEST, true));
        throw new AuthenticationException;
    }
}
