<?php

declare(strict_types = 1);

namespace SlimRestApi;

require_once 'vendor/autoload.php';
require_once 'Infra/Ini.php';

use CorsSlim\CorsSlim;
use pavlakis\cli\CliRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use SlimRequestParams\RequestResponseArgsObject;
use SlimRestApi\Infra\Ini;

class SlimRestApi extends App
{
    public function __construct()
    {
        parent::__construct();

        // translate php errors into 500-exceptions
        set_error_handler(function ($severity, $message, $file, $line) {
            if (error_reporting() & $severity) {
                throw new \ErrorException($message, 500, $severity, $file, $line);
            }
        });

        // translate assert into 500-exceptions
        /** @noinspection PhpUnusedParameterInspection */
        assert_options(ASSERT_CALLBACK, function ($file, $line, $msg, $desc = null) {
            throw new \ErrorException($desc, 500, E_ERROR, $file, $line);
        });

        // middleware that translates exceptions into 'normal' responses
        $this->add(function (
            ServerRequestInterface $request,
            ResponseInterface $response,
            callable $next)
        : ResponseInterface {
            try {
                return $next($request, $response);
            } catch (\Throwable $e) {
                $status = $e->getCode();
                if (!is_integer($status) or $status < 100 or $status > 599) {
                    $status = 500;
                }
                if ($status >= 500) {
                    error_log($e->getMessage() . ' @ ' . $e->getFile() . '(' . $e->getLine() . ')');
                }
                return $response->withJson($e->getMessage(), $status);
            }
        });

        // make sure we have the correct ini-settings
        ini_set('display_errors', "0");
        ini_set('assert.active', "1");
        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        @ini_set('zend.assertions', "1");
        ini_set('assert.exception', "0");
        error_reporting(E_ALL);

        // listen for cli calls, routes can be CLI-enabled with middleware
        $this->add(new CliRequest);

        // add strategy that combines url-, query- and post-parameters into one arg-object
        $this->getContainer()['foundHandler'] = function (): callable {
            return new RequestResponseArgsObject;
        };

        // blank 404 pages
        /** @noinspection PhpUnusedParameterInspection */
        $this->getContainer()['notFoundHandler'] = function ($c): callable {
            /** @noinspection PhpUnusedParameterInspection */
            return function (
                ServerRequestInterface $request,
                ResponseInterface $response)
            : ResponseInterface {
                return $response->withStatus(404);
            };
        };

        // blank 405 pages
        /** @noinspection PhpUnusedParameterInspection */
        $this->getContainer()['notAllowedHandler'] = function ($c): callable {
            /** @noinspection PhpUnusedParameterInspection */
            return function (
                ServerRequestInterface $request,
                ResponseInterface $response,
                array $methods)
            : ResponseInterface {
                return $response
                    ->withStatus(405)
                    ->withHeader('Allow', implode(', ', $methods));
            };
        };

        // add cors headers to response
        $get_cors_origin = function (): array {
            $origin = [];
            if (array_key_exists('HTTP_ORIGIN', $_SERVER)
                and in_array(strtolower($_SERVER["HTTP_ORIGIN"]), Ini::get('cors_origin'))
            ) {
                $origin[] = $_SERVER["HTTP_ORIGIN"];
            }
            return $origin;
        };
        $this->add(new CorsSlim([
            "origin" => $get_cors_origin(),
            "exposeHeaders" => Ini::get('cors_expose_headers'),
            "maxAge" => Ini::get('cors_max_age'),
            // 1 or "TRUE" from the ini file are both not working as values for allowCredentials
            "allowCredentials" => Ini::get('cors_allow_credentials') ? true : false,
            "allowMethods" => Ini::get('cors_allow_methods'),
            "allowHeaders" => Ini::get('cors_allow_headers'),
        ]));

    }
}
