<?php /** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace SlimRestApi\Middleware {

    use DateInterval;
    use DateTime;
    use DateTimeInterface;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;

    class Cacheable
    {
        protected $expiration;

        public function __construct(int $expiration = 60)
        {
            $this->expiration = $expiration;
        }

        public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
        {
            assert($request->getMethod() === 'GET');
            return $next($request,
                $response
                    ->withHeader("cache-control", "public, max-age=" . $this->expiration)
                    ->withHeader("last-modified", (new DateTime())->format(DateTimeInterface::RFC1123))
                    ->withHeader("expires", (new DateTime())->add(new DateInterval("PT" . $this->expiration . "S"))->format(DateTimeInterface::RFC1123))
            );
        }
    }
}