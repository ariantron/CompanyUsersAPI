<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Dto\LoginRequest;
use App\Dto\LoginResponse;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Enum\UserRoleEnum;
use ApiPlatform\Metadata\ApiProperty;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: "users")]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/users',
            controller: 'App\Controller\UserController::index',
            description: 'Retrieve a paginated list of users with filters.'
        ),
        new Get(
            uriTemplate: '/users/{id}',
            controller: 'App\Controller\UserController::show',
            description: 'Retrieve a single user by ID.'
        ),
        new Get(
            uriTemplate: '/user',
            controller: 'App\Controller\UserController::user',
            description: 'Retrieve get request user.'
        ),
        new Post(
            uriTemplate: '/users',
            controller: 'App\Controller\UserController::store',
            description: 'Create a new user.'
        ),
        new Put(
            uriTemplate: '/users/{id}',
            controller: 'App\Controller\UserController::update',
            description: 'Update a user by ID.'
        ),
        new put(
            uriTemplate: '/users/{id}/set-company/{companyId}',
            controller: 'App\Controller\UserController::setCompany',
            description: 'Set a user to a company.'
        ),
        new put(
            uriTemplate: '/users/{id}/unset-company',
            controller: 'App\Controller\UserController::unsetCompany',
            description: 'Unset a user from a company.'
        ),
        new Delete(
            uriTemplate: '/users/{id}',
            controller: 'App\Controller\UserController::delete',
            description: 'Delete a user by ID.'
        ),
        new Post(
            uriTemplate: 'login',
            controller: 'App\Controller\AuthController::login',
            description: 'Login a user.',
            input: LoginRequest::class,
            output: LoginResponse::class
        )
    ]
)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['user:read', 'company:read'])]
    #[ApiProperty(
        description: 'Unique identifier for the user.',
        identifier: true,
        openapiContext: [
            'type' => 'integer',
            'example' => 2
        ]
    )]
    private int $id;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 100)]
    #[Assert\Regex(
        pattern: '/^[A-Za-z\s]*$/',
        message: 'The name can only contain letters and spaces.'
    )]
    #[Assert\Regex(
        pattern: '/[A-Z]/',
        message: 'The name must contain at least one uppercase letter.'
    )]
    #[Groups(['user:read', 'user:write', 'company:read'])]
    #[ApiProperty(
        description: 'Full name of the user.',
        openapiContext: [
            'type' => 'string',
            'example' => 'John Doe'
        ]
    )]
    private string $name;

    #[ORM\Column(type: 'string', length: 100, enumType: UserRoleEnum::class)]
    #[Assert\NotBlank]
    #[Groups(['user:read', 'user:write'])]
    #[ApiProperty(
        description: 'Role of the user in the system.',
        openapiContext: [
            'type' => 'string',
            'enum' => ['ROLE_SUPER_ADMIN', 'ROLE_COMPANY_ADMIN', 'ROLE_USER']
        ]
    )]
    private UserRoleEnum $role;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    #[ApiProperty(
        description: 'Company associated with the user.',
        openapiContext: [
            'type' => 'object',
            'example' => ['id' => 1, 'name' => 'Acme Corporation']
        ]
    )]
    private ?Company $company = null;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 100)]
    #[Groups(['user:read', 'user:write'])]
    #[ApiProperty(
        description: 'Unique username for user authentication.',
        openapiContext: [
            'type' => 'string',
            'example' => 'johndoe'
        ]
    )]
    private string $username;

    #[ORM\Column(type: 'string')]
    #[Assert\NotBlank]
    #[Assert\Length(min: 6, max: 255)]
    #[Groups(['user:write'])]
    #[ApiProperty(
        description: 'Hashed password for user authentication.',
        openapiContext: [
            'type' => 'string',
            'writeOnly' => true
        ]
    )]
    private string $password;

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getRole(): UserRoleEnum
    {
        return $this->role;
    }

    public function setRole(UserRoleEnum $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): self
    {
        $this->company = $company;
        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }

    public function isSuperAdmin(): bool
    {
        return $this->getRole() == UserRoleEnum::ROLE_SUPER_ADMIN;
    }

    public function isCompanyAdmin(): bool
    {
        return $this->getRole() == UserRoleEnum::ROLE_COMPANY_ADMIN;
    }

    public function isJustUser(): bool
    {
        return $this->getRole() == UserRoleEnum::ROLE_USER;
    }
}
