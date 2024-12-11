<?php

namespace App\Tests;

use App\Entity\Company;
use App\Entity\User;
use App\Enum\UserRoleEnum;
use App\Repository\CompanyRepository;
use App\Service\JwtService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;

class CompanyTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private CompanyRepository $companyRepository;
    private JwtService $jwtService;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->companyRepository = $container->get(CompanyRepository::class);
        $params = $container->get(ParameterBagInterface::class);
        $this->jwtService = new JwtService($params);
    }

    /**
     * Create a test user with specified role and optional company
     */
    private function createTestUser(UserRoleEnum $role): User
    {
        $user = new User();
        $user->setUsername('test_' . uniqid());
        $user->setName('Test User');
        $user->setRole($role);

        $plaintextPassword = 'testpassword123';
        $hashedPassword = password_hash($plaintextPassword, PASSWORD_DEFAULT);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createSuperAdmin(): User
    {
        return $this->createTestUser(UserRoleEnum::ROLE_SUPER_ADMIN);
    }

    /**
     * Generate JWT token for a user
     */
    private function generateJwtToken(User $user): string
    {
        $userData = [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'role' => $user->getRole()
        ];

        return $this->jwtService->generateJWT($userData);
    }

    /**
     * Test retrieving companies (index action)
     */
    public function testIndexAction(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $token = $this->generateJwtToken($superAdmin);

        $company1 = new Company();
        $company1->setName('Company A');
        $company2 = new Company();
        $company2->setName('Company B');
        $this->entityManager->persist($company1);
        $this->entityManager->persist($company2);
        $this->entityManager->flush();

        $this->client->request('GET', '/companies', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(2, $responseData);
        $this->assertSame('Company A', $responseData[0]['name']);
        $this->assertSame('Company B', $responseData[1]['name']);
    }

    /**
     * Test creating a new company
     */
    public function testCreateCompanyAction(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $token = $this->generateJwtToken($superAdmin);

        $companyData = [
            'name' => 'New Test Company'
        ];

        $this->client->request('POST', '/companies', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ], json_encode($companyData));

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());

        $company = $this->companyRepository->findOneBy(['name' => 'New Test Company']);
        $this->assertNotNull($company);
        $this->assertSame('New Test Company', $company->getName());
    }

    /**
     * Test creating a company with invalid data
     */
    public function testCreateCompanyWithInvalidData(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $token = $this->generateJwtToken($superAdmin);

        $companyData = [
            'name' => 'Too'
        ];

        $this->client->request('POST', '/companies', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ], json_encode($companyData));

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
    }

    /**
     * Test updating a company
     */
    public function testUpdateCompanyAction(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $token = $this->generateJwtToken($superAdmin);

        $company = new Company();
        $company->setName('Original Company');
        $this->entityManager->persist($company);
        $this->entityManager->flush();

        $updateData = [
            'name' => 'Updated Company Name'
        ];

        $this->client->request('PUT', '/companies/' . $company->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ], json_encode($updateData));

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $updatedCompany = $this->companyRepository->findById($company->getId());
        $this->assertSame('Updated Company Name', $updatedCompany->getName());
    }

    /**
     * Test deleting a company
     */
    public function testDeleteCompanyAction(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $token = $this->generateJwtToken($superAdmin);

        $company = new Company();
        $company->setName('Company to Delete');
        $this->entityManager->persist($company);
        $this->entityManager->flush();

        $companyId = $company->getId();

        $this->client->request('DELETE', '/companies/' . $companyId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ]);

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());

        $deletedCompany = $this->companyRepository->findById($companyId);
        $this->assertNull($deletedCompany);
    }

    /**
     * Test accessing companies without proper authorization
     */
    public function testUnauthorizedAccess(): void
    {
        $user = $this->createTestUser(UserRoleEnum::ROLE_USER);

        $token = $this->generateJwtToken($user);

        $companyData = [
            'name' => 'Unauthorized Company'
        ];

        $this->client->request('POST', '/companies', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token
        ], json_encode($companyData));

        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}