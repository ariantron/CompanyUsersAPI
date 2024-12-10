<?php

namespace App\Repository;

use App\Entity\Company;
use App\Entity\User;
use App\Service\JwtService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    private ParameterBagInterface $params;

    public function __construct(ManagerRegistry $registry, ParameterBagInterface $params)
    {
        $this->params = $params;
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
        if ($filters['companyId']) {
            $queryBuilder->andWhere('u.company = :companyId')
                ->setParameter('companyId', $filters['companyId']);
        }
        if ($filters['role']) {
            $queryBuilder->andWhere('u.role = :role')
                ->setParameter('role', $filters['role']);
        }
        $pageSize = 12;
        $queryBuilder->orderBy('u.id', 'DESC')
            ->setFirstResult(($filters['page'] - 1) * $pageSize)
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

    /**
     * Login a user.
     *
     * @param string $username
     * @param string $password
     * @return string|null
     */
    public function login(string $username, string $password): ?string
    {
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['username' => $username]);
        if (!$user || !password_verify($password, $user->getPassword())) {
            return null;
        }
        return (new JwtService($this->params))->generateJWT([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'role' => $user->getRole()
        ]);
    }
}
