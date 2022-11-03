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
            $return = $next($request, $response);
            $IfNoneMatch = $request->getHeader("If-None-Match")[0] ?? null;
            $Etag = sha1($return->getBody()->getContents());
            if ($IfNoneMatch === $Etag) {
                return (new Response())->withStatus(304);
            }
            return $return->withHeader("Etag", $Etag);
        }
    }
}