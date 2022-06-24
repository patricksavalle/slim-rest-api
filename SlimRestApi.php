<?php /** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace SlimRestApi;

require_once 'vendor/autoload.php';

use ErrorException;
use pavlakis\cli\CliRequest;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use SlimRequestParams\RequestResponseArgsObject;
use Throwable;

class SlimRestApi extends App
{
    /** @noinspection PhpUnusedParameterInspection */
    public function __construct()
    {
        parent::__construct();

        // translate php errors into 500-exceptions
        set_error_handler(function ($severity, $message, $file, $line) {
            if (error_reporting() & $severity) {
                throw new ErrorException($message, 500, $severity, $file, $line);
            }
        });

        // translate assert into 500-exceptions
        assert_options(ASSERT_CALLBACK, function ($file, $line, $msg, $desc = null) {
            throw new ErrorException($desc, 500, E_ERROR, $file, $line);
        });

        // middleware that translates exceptions into 'normal' responses
        $this->add(function (
            Request  $request,
            Response $response,
            callable $next): Response {
            try {
                return $next($request, $response);
            } catch (Throwable $e) {
                $status = $e->getCode();
                if (!is_integer($status) or $status < 100 or $status > 599) {
                    $status = 500;
                }
                if ($status >= 500) {
                    error_log($e->getMessage() . ' @ ' . $e->getFile() . '(' . $e->getLine() . ')');
                }
                return $response->withJson(["message" => $e->getMessage(),"code" => $status], $status);
            }
        });

        // make sure we have the correct ini-settings
        ini_set('display_errors', "0");
        ini_set('assert.active', "1");
        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        @ini_set('zend.assertions', "1");
        ini_set('assert.exception', "0");
        error_reporting(E_ALL);

        // listen for cli calls, routes can be made CLI-only with CliRequest-middleware
        $this->add(new CliRequest);

        // add strategy that combines url-, query- and post-parameters into one arg-object
        $this->getContainer()['foundHandler'] = function (): callable {
            return new RequestResponseArgsObject;
        };

        // blank 404 pages
        $this->getContainer()['notFoundHandler'] = function ($c): callable {
            return function (
                Request  $request,
                Response $response): Response {
                return $response->withStatus(404);
            };
        };

        // blank 405 pages
        $this->getContainer()['notAllowedHandler'] = function ($c): callable {
            return function (
                Request  $request,
                Response $response,
                array    $methods): Response {
                return $response
                    ->withStatus(405)
                    ->withHeader('Allow', implode(', ', $methods));
            };
        };
    }
}
