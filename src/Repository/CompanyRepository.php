<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

/**
 * @extends ServiceEntityRepository<Company>
 *
 * This repository handles database operations for the Company entity.
 */
class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    /**
     * Retrieve all companies with pagination and additional filters.
     *
     * @param array $filters Filters to apply, including pageNumber and pageSize.
     * @return Company[] Array of company entities.
     */
    public function index(array $filters): array
    {
        $pageNumber = $filters['pageNumber'] ?? 1;
        $pageSize = $filters['pageSize'] ?? 12;

        $queryBuilder = $this->createQueryBuilder('c')
            ->orderBy('c.id', 'DESC');

        $queryBuilder->setFirstResult(($pageNumber - 1) * $pageSize)
            ->setMaxResults($pageSize);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Get all users associated with a specific company.
     *
     * @param Company $company
     * @return User[] Array of User entities associated with the given company.
     */
    public function getUsers(Company $company): array
    {
        return $company->getUsers()->toArray();
    }

    /**
     * Find a company by its id.
     *
     * @param int $id The id of the company.
     * @return Company|null The company entity or null if not found.
     */
    public function findById(int $id): ?Company
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Store a company entity to the database.
     *
     * @param Company $company The company entity to store.
     */
    public function store(Company $company): void
    {
        $this->getEntityManager()->persist($company);
        $this->getEntityManager()->flush();
    }

    /**
     * Update a company entity in the database.
     *
     * @param Company $company The company entity to update.
     * @throws Exception
     */
    public function update(Company $company): void
    {
        $this->getEntityManager()->persist($company);
        $this->getEntityManager()->flush();
    }

    /**
     * Delete a company entity from the database.
     *
     * @param Company $company The company entity to delete.
     */
    public function delete(Company $company): void
    {
        $this->getEntityManager()->remove($company);
        $this->getEntityManager()->flush();
    }
}
