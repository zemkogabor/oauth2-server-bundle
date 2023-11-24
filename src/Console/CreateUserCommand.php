<?php

declare(strict_types = 1);

namespace Zemkog\OAuth2ServerBundle\Console;

use Zemkog\OAuth2ServerBundle\Entity\UserEntity;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CreateUserCommand extends Command
{
    protected static $defaultName = 'user:create';
    protected static $defaultDescription = 'Creates a new user.';

    public function __construct(protected EntityManager $entityManager, string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'User email');
        $this->addArgument('name', InputArgument::REQUIRED, 'User name');
        $this->addArgument('password', InputArgument::REQUIRED, 'Client password');
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');
        $name = $input->getArgument('name');
        $password = $input->getArgument('password');

        $user = new UserEntity();
        $user->setEmail($email);
        $user->setName($name);
        $user->setPassword($password);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('<info>User created</info>');
        $output->writeln('Email: ' . $user->getEmail());
        $output->writeln('Name: ' . $user->getName());
        $output->writeln('Password: ***');

        return Command::SUCCESS;
    }
}
