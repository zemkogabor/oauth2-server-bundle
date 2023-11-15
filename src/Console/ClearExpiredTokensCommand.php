<?php

declare(strict_types = 1);

namespace Zemkog\OAuth2ServerBundle\Console;

use Zemkog\OAuth2ServerBundle\Entity\AccessTokenEntity;
use Zemkog\OAuth2ServerBundle\Entity\RefreshTokenEntity;
use Zemkog\OAuth2ServerBundle\Repository\AccessTokenRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query\QueryException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use DateTime;
use Doctrine\DBAL\Types\Types;

final class ClearExpiredTokensCommand extends Command
{
    protected static $defaultName = 'clear-expired-tokens';
    protected static $defaultDescription = 'Clears all expired access and refresh tokens.';

    public function __construct(
        protected EntityManager $em,
        protected LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    /**
     * @throws QueryException
     * @throws OptimisticLockException
     * @throws ORMException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->clearExpiredRefreshTokens($io);
        $this->clearExpiredAccessTokens($io);

        return Command::SUCCESS;
    }

    protected function clearExpiredRefreshTokens(SymfonyStyle $io): void
    {
        $numOfClearedRefreshTokens = $this->em->createQueryBuilder()
            ->delete(RefreshTokenEntity::class, 'rt')
            ->where('rt.expiry_at < :expiry_at')
            ->setParameter('expiry_at', new DateTime(), Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->execute();

        $io->success(sprintf(
            'Cleared %d expired refresh token%s.',
            $numOfClearedRefreshTokens,
            $numOfClearedRefreshTokens === 1 ? '' : 's'
        ));
    }

    /**
     * @throws QueryException
     * @throws OptimisticLockException
     * @throws ORMException
     */
    protected function clearExpiredAccessTokens(SymfonyStyle $io): void
    {
        $accessTokenRepository = new AccessTokenRepository($this->em, $this->logger);

        // todo: OR revoked
        $accessTokens = $accessTokenRepository->createQueryBuilder('accessToken')
            ->select('accessToken')
            ->leftJoin('accessToken.refreshTokens', 'refreshTokens')
            ->addCriteria(
                Criteria::create()
                    ->andWhere(Criteria::expr()->isNull('refreshTokens.id'))
                    ->andWhere(Criteria::expr()->lt('accessToken.expiry_at', new DateTime()))
            )
            ->getQuery()
            ->getResult();

        /**
         * @var AccessTokenEntity[] $accessTokens
         */
        foreach ($accessTokens as $accessToken) {
            $this->em->remove($accessToken);
        }
        $this->em->flush();

        $numOfClearedAccessTokens = count($accessTokens);

        $io->success(sprintf(
            'Cleared %d expired access token%s.',
            $numOfClearedAccessTokens,
            $numOfClearedAccessTokens === 1 ? '' : 's'
        ));
    }
}
