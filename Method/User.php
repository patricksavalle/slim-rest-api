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

require_once BASE_PATH . '/Model/User.php';
require_once BASE_PATH . '/Middleware/Authentication.php';
require_once BASE_PATH . '/Exception/UploadException.php';

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SlimRestApi\Exception\UploadException;
use SlimRestApi\Middleware\Authentication;

class User
{
    static public function listTravelers(ServerRequestInterface $request, ResponseInterface $response, \stdClass $args)
    {
        return $response->withJson(\Tripdrive\Model\User::listTravelers($args->folderid));
    }

    static public function deleteTraveler(ServerRequestInterface $request, ResponseInterface $response, \stdClass $args)
    {
        return $response->withJson(\Tripdrive\Model\User::deleteTraveler($args->folderid, $args->userid));
    }

    static public function get(ServerRequestInterface $request, ResponseInterface $response, \stdClass $args)
    {
        $userid = empty($args->userid)
            ? Authentication::user()->uaddress
            : $args->userid;
        return $response->withJson(\Tripdrive\Model\User::get($userid));
    }

    static public function post(ServerRequestInterface $request, ResponseInterface $response, \stdClass $args)
    {
        $result = \Tripdrive\Model\User::post($args->folderid, $args->email);
        activity_log($args->folderid, Authentication::user()->uaddress, 'added', 'traveler', $args->email);
        return $response->withJson($result);
    }

    static public function postCSV(ServerRequestInterface $request, ResponseInterface $response, \stdClass $args)
    {
        if (count($request->getUploadedFiles()) == 0) {
            throw new UploadException(UPLOAD_ERR_NO_FILE);
        }
        $count = 0;
        foreach ($request->getUploadedFiles() as $file) {
            if ($file->getError() != 0) {
                throw new UploadException($file->getError());
            }
            $count += $subcount = \Tripdrive\Model\User::postCSV($file, $args->folderid);
            activity_log($args->folderid, Authentication::user()->uaddress, "imported", "$subcount travelers", $file->getClientFilename());
        }
        return $response->withJson($count, 201);
    }

    static public function patch(ServerRequestInterface $request, ResponseInterface $response, \stdClass $args)
    {
        return $response->withJson(\Tripdrive\Model\User::patch($args));
    }

    static public function patchEmail(ServerRequestInterface $request, ResponseInterface $response, \stdClass $args)
    {
        (new TwoFactorAuth)
            ->addParams($args)
            ->addTrigger('Model/User.php', ['\Tripdrive\Model\User', 'patchEmail'], [Authentication::user()->uaddress, $args->receiver])
            ->addTrigger('Model/Session.php', ['\Tripdrive\Model\Session', 'post'], [$args->receiver])
            ->send();
        return $response;
    }

    static public function patchPassword(ServerRequestInterface $request, ResponseInterface $response, \stdClass $args)
    {
        return $response->withJson(\Tripdrive\Model\User::patchPassword($args->password));
    }

}
