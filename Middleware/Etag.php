<?php

declare(strict_types=1);

namespace SlimRestApi\Middleware {

    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Slim\Http\Response;

    class Etag
    {
        public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
        {
            assert($request->getMethod() === 'GET');
            $return = $next($request, $response);
            $IfNoneMatch = $request->getHeader("If-None-Match")[0] ?? null;
            $ETag = md5($return->getBody()->getContents());
            return ($IfNoneMatch === $ETag)
                ? (new Response)->withStatus(304)
                : $return->withHeader("ETag", $ETag);
        }
    }
}