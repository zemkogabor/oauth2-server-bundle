<?php

declare(strict_types = 1);

namespace Zemkog\OAuth2ServerBundle\Controller;

use Zemkog\OAuth2ServerBundle\Repository\AccessTokenRepository;
use Zemkog\OAuth2ServerBundle\Repository\RefreshTokenRepository;
use Zemkog\OAuth2ServerBundle\Repository\ScopeRepository;
use Zemkog\OAuth2ServerBundle\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Slim\Http\Response;

class UserController
{
    public const REQUEST_ATTRIBUTE_OAUTH_USER_ID = 'oauth_user_id';
    public const REQUEST_ATTRIBUTE_OAUTH_CLIENT_ID = 'oauth_client_id';
    public const REQUEST_ATTRIBUTE_OAUTH_SCOPES= 'oauth_scopes';
    public const REQUEST_ATTRIBUTE_OAUTH_ACCESS_TOKEN_ID= 'oauth_access_token_id';

    public function __construct(
        protected EntityManager $em,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * @param ServerRequestInterface $request
     * @param Response $response
     * @return ResponseInterface
     */
    public function actionActive(ServerRequestInterface $request, Response $response): Response
    {
        $userRepository = new UserRepository($this->em, $this->logger);
        $user = $userRepository->findOneBy(['id' => $request->getAttribute(static::REQUEST_ATTRIBUTE_OAUTH_USER_ID), 'deleted_at' => null]);

        $params = [];

        if (in_array(ScopeRepository::SCOPE_BASIC, $request->getAttribute(static::REQUEST_ATTRIBUTE_OAUTH_SCOPES, []), true)) {
            $params = [
                'id' => $user->getIdentifier(),
            ];
        }

        if (in_array(ScopeRepository::SCOPE_EMAIL, $request->getAttribute(static::REQUEST_ATTRIBUTE_OAUTH_SCOPES, []), true)) {
            $params['email'] = $user->getEmail();
        }

        if (in_array(ScopeRepository::SCOPE_NAME, $request->getAttribute(static::REQUEST_ATTRIBUTE_OAUTH_SCOPES, []), true)) {
            $params['name'] = $user->getName();
        }

        return $response->withJson([$params]);
    }

    /**
     * @param ServerRequestInterface $request
     * @param Response $response
     * @return ResponseInterface
     * @throws ORMException
     */
    public function actionLogout(ServerRequestInterface $request, Response $response): Response
    {
        // Revoke the access token of the current user.
        $accessTokenRepository = new AccessTokenRepository($this->em, $this->logger);
        $tokenId = $request->getAttribute(static::REQUEST_ATTRIBUTE_OAUTH_ACCESS_TOKEN_ID);
        $accessTokenRepository->revokeAccessToken($tokenId);
        $accessToken = $accessTokenRepository->findOneBy(['token' => $tokenId]);
        $accessToken->setIsRevoke(true);

        // Related refresh tokens must also be deleted.
        $refreshTokenRepository = new RefreshTokenRepository($this->em, $this->logger);
        $refreshToken = $refreshTokenRepository->findOneBy(['accessToken' => $accessToken]);
        $refreshTokenRepository->revokeRefreshToken($refreshToken->getIdentifier());

        return $response;
    }
}
