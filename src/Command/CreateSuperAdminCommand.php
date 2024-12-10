<?php

namespace App\Command;

use App\Entity\User;
use App\Enum\UserRoleEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-superadmin',
    description: 'Create a new user with a specified role',
)]
class CreateSuperAdminCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('password', InputArgument::REQUIRED, 'Password of Super Admin User');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Create the user
        $user = new User();
        $user->setName('Super Admin');
        $user->setUsername('superadmin');
        $user->setPassword(password_hash($input->getArgument('password'), PASSWORD_DEFAULT));
        $user->setRole(UserRoleEnum::ROLE_SUPER_ADMIN);

        // Persist and save
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success("Super-Admin user was successfully created!");

        return Command::SUCCESS;
    }
}
