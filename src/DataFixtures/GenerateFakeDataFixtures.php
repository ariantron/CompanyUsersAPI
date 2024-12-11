<?php

namespace App\DataFixtures;

use App\Entity\Company;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class GenerateFakeDataFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $output = new ConsoleOutput();

        $this->createSuperAdmin($manager);
        $output->writeln("\nSuper Admin User was successfully created! (Password: 123456)\n");

        $companyProgressBar = new ProgressBar($output, 10);
        $companyProgressBar->start();

        $companies = [];
        for ($i = 0; $i < 10; $i++) {
            $company = $this->createFakeCompany($faker, $manager);
            $companies[] = $company;
            $companyProgressBar->advance();
        }

        $companyProgressBar->finish();
        $output->writeln("\nCompanies created successfully!\n");

        $userProgressBar = new ProgressBar($output, 100);
        $userProgressBar->start();

        for ($i = 0; $i < 100; $i++) {
            $user = $this->createFakeUser($faker, $manager);
            $user->setCompany($companies[array_rand($companies)]);
            $manager->persist($user);
            $userProgressBar->advance();
        }

        $userProgressBar->finish();
        $output->writeln("\nUsers created successfully!\n");

        $manager->flush();

        $output->writeln("Fake Data was successfully generated!");
    }

    private function createSuperAdmin(ObjectManager $manager): void
    {
        $user = new User();
        $user->setName('Super Admin');
        $user->setUsername('superadmin');
        $user->setPassword(password_hash('123456', PASSWORD_DEFAULT));
        $user->setRole(UserRoleEnum::ROLE_SUPER_ADMIN);

        $manager->persist($user);
    }

    private function createFakeCompany(Generator $faker, ObjectManager $manager): Company
    {
        $company = new Company();
        $company->setName($faker->company);
        $manager->persist($company);

        $this->createFakeUser($faker, $manager, $company);

        return $company;
    }

    private function createFakeUser(Generator $faker, ObjectManager $manager, ?Company $company = null): User
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

        $manager->persist($user);

        return $user;
    }
}
