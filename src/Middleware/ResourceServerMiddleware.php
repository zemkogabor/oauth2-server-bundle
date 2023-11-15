<?php

declare(strict_types = 1);

namespace Zemkog\OAuth2ServerBundle\Middleware;

use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Http\Response;

class ResourceServerMiddleware
{
    private ResourceServer $server;

    public function __construct(ResourceServer $server)
    {
        $this->server = $server;
    }

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): Response
    {
        $response = $handler->handle($request);

        try {
            // Try to respond to the access token request
            $request = $this->server->validateAuthenticatedRequest($request);
        } catch (OAuthServerException $exception) {
            // All instances of OAuthServerException can be converted to a PSR-7 response
            return $exception->generateHttpResponse($response);
        }

        return $handler->handle($request);
    }
}
