<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Retrieve users based on filters.
     *
     * @param array $filters Contains filters such as 'companyId', 'role', 'pageNumber', and 'pageSize'.
     * @return User[] Returns an array of User objects.
     */
    public function index(array $filters = []): array
    {
        $queryBuilder = $this->createQueryBuilder('u');

        // Filter by company ID if provided
        if (isset($filters['companyId'])) {
            $queryBuilder->andWhere('u.company = :companyId')
                ->setParameter('companyId', $filters['companyId']);
        }

        // Filter by role if provided
        if (isset($filters['role'])) {
            $queryBuilder->andWhere('u.role = :role')
                ->setParameter('role', $filters['role']);
        }

        // Handle pagination if pageNumber and pageSize are provided
        $pageNumber = $filters['pageNumber'] ?? 1;
        $pageSize = $filters['pageSize'] ?? 12;
        $queryBuilder->orderBy('u.id', 'DESC')
            ->setFirstResult(($pageNumber - 1) * $pageSize)
            ->setMaxResults($pageSize);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Find user by ID.
     *
     * @param int $id
     * @return User|null Returns a User object or null
     */
    public function findById(int $id): ?User
    {
        return $this->find($id);
    }

    /**
     * Store a new user.
     *
     * @param User $user
     * @return void
     */
    public function store(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Update an existing user.
     *
     * @param User $user
     * @return void
     */
    public function update(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Setting a company to a user.
     *
     * @param User $user
     * @param Company $company
     * @return void
     */
    public function setCompany(User $user, Company $company): void
    {
        $user->setCompany($company);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Remove a user from their company.
     *
     * @param User $user
     * @return void
     */
    public function unsetCompany(User $user): void
    {
        $user->setCompany(null);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Delete a user.
     *
     * @param User $user
     * @return void
     */
    public function delete(User $user): void
    {
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();
    }
}
