<?php /** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace SlimRestApi\Middleware {

    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;

    class NoCache
    {
        protected $expiration;

        public function __construct(int $expiration = 60)
        {
            $this->expiration = $expiration;
        }

        public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
        {
            return $next($request, $response)->withHeader("cache-control", "no-cache");
        }
    }
}