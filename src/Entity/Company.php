<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\CompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\ApiProperty;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
#[ORM\Table(name: "companies")]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/companies',
            controller: 'App\Controller\CompanyController::index',
            description: 'Retrieve a paginated list of companies with filters.'
        ),
        new Get(
            uriTemplate: '/companies/{id}',
            controller: 'App\Controller\CompanyController::show',
            description: 'Retrieve a single company by ID.'
        ),
        new GetCollection(
            uriTemplate: '/companies/{id}/users',
            controller: 'App\Controller\CompanyController::getUsers',
            description: 'Retrieve users of company.'
        ),
        new Post(
            uriTemplate: '/companies',
            controller: 'App\Controller\CompanyController::create',
            description: 'Create a new company.'
        ),
        new Put(
            uriTemplate: '/companies/{id}',
            controller: 'App\Controller\CompanyController::update',
            description: 'Update a company by ID.'
        ),
        new Delete(
            uriTemplate: '/companies/{id}',
            controller: 'App\Controller\CompanyController::delete',
            description: 'Delete a company by ID.'
        )
    ]
)]
class Company
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['company:read', 'user:read'])]
    #[ApiProperty(
        description: 'Unique identifier for the company.',
        identifier: true,
        openapiContext: [
            'type' => 'integer',
            'example' => 1
        ]
    )]
    private int $id;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 5, max: 100)]
    #[Groups(['company:read', 'company:write', 'user:read'])]
    #[ApiProperty(
        description: 'Name of the company, must be unique.',
        openapiContext: [
            'type' => 'string',
            'example' => 'Acme Corporation'
        ]
    )]
    private string $name;

    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'company')]
    #[ApiProperty(
        description: 'Collection of users associated with this company.',
        openapiContext: [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'example' => ['id' => 1, 'name' => 'John Doe']
            ]
        ]
    )]
    private $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

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

    public function getUsers(): Collection
    {
        return $this->users;
    }
}
