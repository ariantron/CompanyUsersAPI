<?php

namespace App\DataFixtures;

use App\Entity\Company;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class UserDataFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $output = new ConsoleOutput();

        // Create Super Admin first, without any company association
        $this->createSuperAdmin($manager);
        $output->writeln("\nSuper Admin User was successfully created! (Password: 123456)\n");

        // Get all companies to assign users to
        $companies = $manager->getRepository(Company::class)->findAll();

        $userProgressBar = new ProgressBar($output, 100);
        $userProgressBar->start();

        for ($i = 0; $i < 100; $i++) {
            $this->createFakeUser($faker, $manager, $companies);
            $userProgressBar->advance();
        }

        $userProgressBar->finish();
        $output->writeln("\nUsers created successfully!\n");

        $manager->flush();
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

    private function createFakeUser(Generator $faker, ObjectManager $manager, array $companies): void
    {
        $user = new User();
        $user->setName($faker->name);
        $user->setUsername($faker->userName);
        $user->setPassword(password_hash((string)rand(100000, 999999), PASSWORD_DEFAULT));

        // Randomly assign a company
        if (!empty($companies)) {
            $user->setCompany($companies[array_rand($companies)]);
        }

        $user->setRole(UserRoleEnum::ROLE_USER);

        $manager->persist($user);
    }

    public function getDependencies(): array
    {
        return [
            CompanyDataFixture::class
        ];
    }
}
