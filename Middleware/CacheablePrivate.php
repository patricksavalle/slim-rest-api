<?php /** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace SlimRestApi\Middleware {

    use DateInterval;
    use DateTime;
    use DateTimeInterface;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;

    class CacheablePrivate
    {
        protected $expiration;

        public function __construct(int $expiration = 60)
        {
            $this->expiration = $expiration;
        }

        public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
        {
            assert($request->getMethod() === 'GET');
            return $next($request, $response->withHeader("cache-control", "private, max-age=" . $this->expiration));
        }
    }
}