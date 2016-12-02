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

require_once BASE_PATH . '/Middleware/Authentication.php';
require_once BASE_PATH . '/Model/Authorisation.php';
require_once BASE_PATH . '/Model/Activity.php';

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SlimRestApi\Middleware\Authentication;

class Authorisation
{
    static public function listUsers(ServerRequestInterface $request, ResponseInterface $response, \stdClass $args)
    {
        return $response->withJson(\Tripdrive\Model\Authorisation::listUsers($args->folderid));
    }

    static public function listFolders(ServerRequestInterface $request, ResponseInterface $response, \stdClass $args)
    {
        return $response->withJson(\Tripdrive\Model\Authorisation::listFolders($args->userid));
    }

    static public function post(ServerRequestInterface $request, ResponseInterface $response, \stdClass $args)
    {
        (new TwoFactorAuth)
            ->addParams([
                "sender" => Authentication::user()->email,
                "sendername" => Authentication::user()->fullname
            ])
            ->addParams($args)
            ->addTrigger('Model/User.php', ['\Tripdrive\Model\User', 'activateEmail'], [$args->receiver])
            ->addTrigger('Model/Authorisation.php', ['\Tripdrive\Model\Authorisation', 'post'], [$args->folderid, $args->receiver, Authentication::user()->uaddress])
            ->addTrigger('Model/Activity.php', 'activity_log', [$args->folderid, null, 'accepted', 'authorisation', $args->receiver])
            ->addTrigger('Model/Session.php', ['\Tripdrive\Model\Session', 'post'], [$args->receiver])
            ->send();
        activity_log($args->folderid, Authentication::user()->uaddress, 'sent', 'authorisation', $args->receiver);
        return $response;
    }

    static public function delete(ServerRequestInterface $request, ResponseInterface $response, \stdClass $args)
    {
        return $response->withJson(\Tripdrive\Model\Authorisation::delete($args->folderid, $args->userid));
    }
}