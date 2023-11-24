<?php

declare(strict_types = 1);

namespace Zemkog\OAuth2ServerBundle\Controller;

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Http\Response;

class AccessTokenController
{
    public function __construct(
        protected AuthorizationServer $authorizationServer,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @param Response $response
     * @return Response
     */
    public function actionAccessToken(ServerRequestInterface $request, Response $response): ResponseInterface
    {
        try {
            // Try to respond to the access token request
            return $this->authorizationServer->respondToAccessTokenRequest($request, $response);
        } catch (OAuthServerException $exception) {
            // All instances of OAuthServerException can be converted to a PSR-7 response
            return $exception->generateHttpResponse($response);
        }
    }
}
