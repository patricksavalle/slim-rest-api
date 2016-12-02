<?php

declare(strict_types = 1);

namespace SlimRestApi;

define("BASE_PATH", dirname(__FILE__));

require 'vendor/autoload.php';
require BASE_PATH . '/Infra/Ini.php';

use CorsSlim\CorsSlim;
use Slim\App;
use SlimRequestParams\RequestResponseArgsObject;
use SlimRestApi\Infra\Ini;

class SlimRestApi extends App
{
    public function __construct()
    {
        parent::__construct();
        $c = $this->getContainer();

        // make sure we have the correct ini-settings
        ini_set('display_errors', "0");
        ini_set('assert.active', "1");
        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        @ini_set('zend.assertions', "1");
        ini_set('assert.exception', "0");
        error_reporting(E_ALL);

        // translate php errors into 500-exceptions
        set_error_handler(function ($severity, $message, $file, $line) {
            if (error_reporting() & $severity) {
                throw new \ErrorException($message, 500, $severity, $file, $line);
            }
        });

        // translate assert into 500-exceptions
        assert_options(ASSERT_CALLBACK, function ($file, $line, $msg, $desc = null) {
            throw new \ErrorException($desc, 500, E_ERROR, $file, $line);
        });

        // translate exceptions into 'normal' responses
        $this->add(new Middleware\ExceptionHandling);

        // add cors headers to response
        $getCorsOrigin = function () {
            $origin = [];
            if (array_key_exists('HTTP_ORIGIN', $_SERVER)
                and in_array(strtolower($_SERVER["HTTP_ORIGIN"]), Ini::get('cors_origin'))
            ) {
                $origin[] = $_SERVER["HTTP_ORIGIN"];
            }
            return $origin;
        };
        $this->add(new CorsSlim([
            "origin" => $getCorsOrigin(),
            "exposeHeaders" => Ini::get('cors_expose_headers'),
            "maxAge" => Ini::get('cors_max_age'),
            // 1 or "TRUE" from the ini file are both not working as values for allowCredentials
            "allowCredentials" => Ini::get('cors_allow_credentials') ? true : false,
            "allowMethods" => Ini::get('cors_allow_methods'),
            "allowHeaders" => Ini::get('cors_allow_headers'),
        ]));

        // add strategy that combines url-, query- and post-parameters into one arg-object
        $c['foundHandler'] = function () {
            return new RequestResponseArgsObject;
        };

        // blank 404 pages
        $c['notFoundHandler'] = function ($c) {
            return function ($request, $response) {
                return $response->withStatus(404);
            };
        };

        // blank 405 pages
        $c['notAllowedHandler'] = function ($c) {
            return function ($request, $response, $methods) {
                return $response
                    ->withStatus(405)
                    ->withHeader('Allow', implode(', ', $methods));
            };
        };
    }

    protected function showHomePage($rq, $rsp, $args)
    {
        echo "<h1>SLIM REST-API</h1>";
        echo "<strong>Programmed by patrick@patricksavalle.com</strong>";
        echo "<h2>Methods</h2>";
        echo "<table>";
        foreach ($this->router->getRoutes() as $route) {
            echo "<tr>";
            foreach ($route->getMethods() as $method) {
                echo "<td>{$method}</td><td>{$route->getPattern()}</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
        return $rsp;
    }
}
