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

require_once BASE_PATH . '/Exception/AuthenticationException.php';
require_once BASE_PATH . '/Middleware/Authentication.php';
require_once BASE_PATH . '/Model/Session.php';

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SlimRestApi\Exception\AuthenticationException;
use SlimRestApi\Middleware\Authentication;

class Session
{
    static public function post(ServerRequestInterface $request, ResponseInterface $response, \stdClass $args)
    {
        if (null !== Authentication::token()) {
            throw new AuthenticationException('SESSION AUTH not allowed on this method', 400);
        }
        $result = \Tripdrive\Model\Session::post();
        activity_log(null, Authentication::user()->uaddress, 'logged in');
        return $response->withJson($result);
    }

    static public function delete(ServerRequestInterface $request, ResponseInterface $response, \stdClass $args)
    {
        if (null === Authentication::token()) {
            throw new AuthenticationException('BASIC AUTH not allowed on this method', 400);
        }
        $result = \Tripdrive\Model\Session::delete();
        activity_log(null, Authentication::user()->uaddress, 'logged out');
        return $response->withJson($result);
    }

    static public function sendLogin(ServerRequestInterface $request, ResponseInterface $response, \stdClass $args)
    {
        (new TwoFactorAuth)
            ->addParams($args)
            ->addTrigger('Model/User.php', ['\Tripdrive\Model\User', 'activateEmail'], [$args->receiver])
            ->addTrigger('Model/Session.php', ['\Tripdrive\Model\Session', 'post'], [$args->receiver])
            ->send();
        return $response;
    }

}
