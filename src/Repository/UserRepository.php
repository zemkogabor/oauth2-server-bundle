<?php

declare(strict_types = 1);

namespace Zemkog\OAuth2ServerBundle\Repository;

use Zemkog\OAuth2ServerBundle\Entity\UserEntity;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * @method UserEntity findOneBy(array $criteria, array $orderBy = null)
 */
class UserRepository extends EntityRepository implements UserRepositoryInterface
{
    public function __construct(protected EntityManager $em, protected LoggerInterface $logger)
    {
        parent::__construct($em, new ClassMetadata(UserEntity::class));
    }

    /**
     * {@inheritdoc}
     */
    public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity): ?UserEntity
    {
        $user = $this->findOneBy(['email' => $username, 'deleted_at' => null]);

        if ($user === null) {
            return null;
        }

        if (!password_verify($password, $user->getPassword())) {
            return null;
        }

        return $user;
    }
}
