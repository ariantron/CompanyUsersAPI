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

class CompanyDataFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $output = new ConsoleOutput();
        $companyProgressBar = new ProgressBar($output, 10);
        $companyProgressBar->start();
        for ($i = 0; $i < 10; $i++) {
            $this->createFakeCompany($faker, $manager);
            $companyProgressBar->advance();
        }
        $companyProgressBar->finish();
        $output->writeln("\nCompanies created successfully!\n");
        $manager->flush();
    }

    private function createFakeCompany(Generator $faker, ObjectManager $manager): void
    {
        $company = new Company();
        $company->setName($faker->company);
        $manager->persist($company);
        // Create a company admin for each company
        $this->createCompanyAdmin($faker, $manager, $company);
    }

    private function createCompanyAdmin(Generator $faker, ObjectManager $manager, Company $company): void
    {
        $user = new User();
        $user->setName($faker->name);
        $user->setUsername($faker->userName);
        $user->setPassword(password_hash((string)rand(100000, 999999), PASSWORD_DEFAULT));
        $user->setCompany($company);
        $user->setRole(UserRoleEnum::ROLE_COMPANY_ADMIN);
        $manager->persist($user);
    }
}
