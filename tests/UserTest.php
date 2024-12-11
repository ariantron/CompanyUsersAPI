<?php

namespace App\Tests;

use App\Entity\Company;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use App\Repository\UserRepository;
use App\Service\JwtService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;

class UserTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManager $entityManager;
    private UserRepository $userRepository;
    private JwtService $jwtService;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        $params = $container->get(ParameterBagInterface::class);
        $this->jwtService = new JwtService($params);
    }

    /**
     * Test user index (list users) endpoint
     */
    public function testIndexUsersEndpoint()
    {
        $companyAdmin = $this->createTestUser(UserRoleEnum::ROLE_COMPANY_ADMIN);
        $token = $this->generateJwtToken($companyAdmin);

        $this->client->request(
            'GET',
            '/users',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
    }

    /**
     * Create a test user with specified role and optional company
     */
    private function createTestUser(UserRoleEnum $role, ?Company $company = null): User
    {
        $user = new User();
        $user->setUsername('test_' . uniqid());
        $user->setName('Test User');
        $user->setRole($role);

        $plaintextPassword = 'testpassword123';
        $hashedPassword = password_hash($plaintextPassword, PASSWORD_DEFAULT);
        $user->setPassword($hashedPassword);

        if ($company) {
            $user->setCompany($company);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Generate a JWT token for a given user
     */
    private function generateJwtToken(User $user): string
    {
        return $this->jwtService->generateJWT([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'role' => $user->getRole()
        ]);
    }

    /**
     * Test forbidden access to user index for regular user
     */
    public function testIndexUsersForbiddenForRegularUser()
    {
        $regularUser = $this->createTestUser(UserRoleEnum::ROLE_USER);
        $token = $this->generateJwtToken($regularUser);

        $this->client->request(
            'GET',
            '/users',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    /**
     * Test creating a new user
     */
    public function testCreateUser()
    {
        $superAdmin = $this->createTestUser(UserRoleEnum::ROLE_SUPER_ADMIN);
        $token = $this->generateJwtToken($superAdmin);

        $company = new Company();
        $company->setName('Test Company');
        $this->entityManager->persist($company);
        $this->entityManager->flush();

        $userData = [
            'name' => 'New Test User',
            'username' => 'newuser_' . uniqid(),
            'password' => 'newpassword123',
            'role' => UserRoleEnum::ROLE_USER->value,
            'company' => $company->getId()
        ];

        $this->client->request(
            'POST',
            '/users',
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json'
            ],
            json_encode($userData)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        $newUser = $this->userRepository->findOneBy(['username' => $userData['username']]);
        $this->assertNotNull($newUser);
        $this->assertEquals($userData['name'], $newUser->getName());
        $this->assertEquals($company->getId(), $newUser->getCompany()->getId());
    }

    /**
     * Test updating a user
     */
    public function testUpdateUser()
    {
        $company = new Company();
        $company->setName('Update Test Company');
        $this->entityManager->persist($company);
        $this->entityManager->flush();

        $originalUser = $this->createTestUser(UserRoleEnum::ROLE_USER, $company);

        $companyAdmin = $this->createTestUser(UserRoleEnum::ROLE_COMPANY_ADMIN, $company);
        $token = $this->generateJwtToken($companyAdmin);

        $updatedUserData = [
            'name' => 'Updated User Name',
            'username' => $originalUser->getUsername(),
            'role' => UserRoleEnum::ROLE_USER->value
        ];

        $this->client->request(
            'PUT',
            '/users/' . $originalUser->getId(),
            [],
            [],
            [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                'CONTENT_TYPE' => 'application/json'
            ],
            json_encode($updatedUserData)
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->entityManager->refresh($originalUser);

        $this->assertEquals($updatedUserData['name'], $originalUser->getName());
    }

    /**
     * Test deleting a user
     */
    public function testDeleteUser()
    {
        $superAdmin = $this->createTestUser(UserRoleEnum::ROLE_SUPER_ADMIN);
        $token = $this->generateJwtToken($superAdmin);

        $userToDelete = $this->createTestUser(UserRoleEnum::ROLE_USER);

        $userToDeleteId = $userToDelete->getId();

        $this->client->request(
            'DELETE',
            '/users/' . $userToDeleteId,
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $deletedUser = $this->userRepository->find($userToDeleteId);
        $this->assertNull($deletedUser);
    }

    /**
     * Test setting company for a user
     */
    public function testSetUserCompany()
    {
        $superAdmin = $this->createTestUser(UserRoleEnum::ROLE_SUPER_ADMIN);
        $token = $this->generateJwtToken($superAdmin);

        $user = $this->createTestUser(UserRoleEnum::ROLE_USER);

        $company = new Company();
        $company->setName('Company 1');
        $this->entityManager->persist($company);
        $this->entityManager->flush();

        $this->client->request(
            'PUT',
            '/users/' . $user->getId() . '/set-company/' . $company->getId(),
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $this->entityManager->refresh($user);

        $this->assertEquals($company->getId(), $user->getCompany()->getId());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}