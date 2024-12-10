<?php

namespace App\Command;

use App\Entity\Company;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:gen-fake',
    description: 'Create Fake Data',
)]
class GenerateFakeDataCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        //
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $faker = Factory::create();

        $this->createSuperAdmin();
        $io->info("Super Admin User was successfully created!");

        $companies = [];
        for ($i = 0; $i < 10; $i++) {
            $company = $this->createFakeCompany($faker);
            $companies[] = $company;
            $io->info("Companies created: " . ($i + 1) . " / 10");
        }

        for ($i = 0; $i < 100; $i++) {
            $user = $this->createFakeUser($faker);
            $user->setCompany($companies[array_rand($companies)]);
            $this->entityManager->persist($user);

            $io->info("Users created: " . ($i + 1) . " / 100");
        }

        // Flush all persisted data in one go for better performance
        $this->entityManager->flush();

        $io->success("Fake Data was successfully generated! (Super Admin password is 123456)");
        return Command::SUCCESS;
    }

    private function createSuperAdmin(): void
    {
        $user = new User();
        $user->setName('Super Admin');
        $user->setUsername('superadmin');
        $user->setPassword(password_hash('123456', PASSWORD_DEFAULT));
        $user->setRole(UserRoleEnum::ROLE_SUPER_ADMIN);

        $this->entityManager->persist($user);
    }

    private function createFakeCompany(Generator $faker): Company
    {
        $company = new Company();
        $company->setName($faker->company);
        $this->entityManager->persist($company);

        // Create a company admin user for the company
        $this->createFakeUser($faker, $company);

        return $company;
    }

    private function createFakeUser(Generator $faker, ?Company $company = null): User
    {
        $user = new User();
        $user->setName($faker->name);
        $user->setUsername($faker->userName);
        $user->setPassword(password_hash((string)rand(100000, 999999), PASSWORD_DEFAULT));

        if ($company) {
            $user->setCompany($company);
            $user->setRole(UserRoleEnum::ROLE_COMPANY_ADMIN);
        } else {
            $user->setRole(UserRoleEnum::ROLE_USER);
        }

        $this->entityManager->persist($user);

        return $user;
    }
}