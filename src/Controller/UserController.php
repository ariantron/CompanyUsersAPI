<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\CompanyRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    private UserRepository $userRepository;
    private CompanyRepository $companyRepository;

    public function __construct(UserRepository $userRepository, CompanyRepository $companyRepository)
    {
        $this->userRepository = $userRepository;
        $this->companyRepository = $companyRepository;
    }

    #[Route('/users', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $filters = [
            'companyId' => $request->query->get('company_id'),
            'role' => $request->query->get('role'),
            'pageNumber' => $request->query->get('page_number', 1),
            'pageSize' => $request->query->get('page_size', 12),
        ];

        $users = $this->userRepository->index($filters);

        return $this->json($users);
    }

    #[Route('/users/{id}', methods: ['GET'])]
    public function show(int $id): Response
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($user);
    }

    #[Route('/users', methods: ['POST'])]
    public function store(Request $request, ValidatorInterface $validator): Response
    {
        $user = new User();
        $user->setName($request->get('name'));
        $user->setRole($request->get('role'));

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->userRepository->store($user);

        return $this->json(['message' => 'User created successfully'], Response::HTTP_CREATED);
    }

    #[Route('/users', methods: ['PUT'])]
    public function update(int $id, Request $request, ValidatorInterface $validator): Response
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        if ($request->get('name')) {
            $user->setName($request->get('name'));
        }
        if ($request->get('role')) {
            $user->setRole($request->get('role'));
        }

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->userRepository->update($user);

        return $this->json(['message' => 'User updated successfully']);
    }

    #[Route('/users/{id}/set-company/{companyId}', methods: ['PUT'])]
    public function setCompany(int $id, int $companyId): Response
    {
        $user = $this->userRepository->findById($id);
        $company = $this->companyRepository->findById($companyId);

        if (!$user) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        } elseif (!$company) {
            return $this->json(['message' => 'Company not found'], Response::HTTP_NOT_FOUND);
        }

        $this->userRepository->setCompany($user, $company);

        return $this->json(['message' => 'User was set to company successfully']);
    }

    #[Route('/users/{id}/unset-company/{companyId}', methods: ['PUT'])]
    public function unsetCompany(int $id): Response
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $this->userRepository->unsetCompany($user);

        return $this->json(['message' => 'User was unset to company successfully']);
    }

    #[Route('/users/{id}', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $user = $this->userRepository->findById($id);

        if (!$user) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $this->userRepository->delete($user);

        return $this->json(['message' => 'User deleted successfully']);
    }
}
