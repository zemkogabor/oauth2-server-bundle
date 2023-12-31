<?php

declare(strict_types = 1);

namespace Zemkog\OAuth2ServerBundle\Repository;

use Zemkog\OAuth2ServerBundle\Entity\ClientEntity;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * @method ClientEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClientEntity[] findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class ClientRepository extends EntityRepository implements ClientRepositoryInterface
{
    private ClientEntity|null $_clientEntity;

    public function __construct(protected EntityManager $em, protected LoggerInterface $logger)
    {
        parent::__construct($em, new ClassMetadata(ClientEntity::class));
    }

    /**
     * {@inheritdoc}
     */
    public function getClientEntity($clientIdentifier): ?ClientEntity
    {
        if (isset($this->_clientEntity)) {
            return $this->_clientEntity;
        }

        return $this->_clientEntity = $this->findOneBy(['uuid' => $clientIdentifier]);
    }

    /**
     * {@inheritdoc}
     */
    public function validateClient($clientIdentifier, $clientSecret, $grantType): bool
    {
        $client = $this->getClientEntity($clientIdentifier);

        if ($client === null) {
            return false;
        }

        if ($client->isConfidential()) {
            // If the client is confidential the secret is required
            if ($clientSecret === null) {
                return false;
            }

            return hash_equals($client->getSecret(), (string) $clientSecret);
        }

        return true;
    }
}
