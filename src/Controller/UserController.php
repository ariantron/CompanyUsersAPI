<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\UserRoleEnum;
use App\Repository\CompanyRepository;
use App\Repository\UserRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends BaseController
{
    private ParameterBagInterface $params;
    private CompanyRepository $companyRepository;
    private UserRepository $userRepository;

    public function __construct(
        ParameterBagInterface $params,
        CompanyRepository     $companyRepository,
        UserRepository        $userRepository
    )
    {
        $this->params = $params;
        $this->companyRepository = $companyRepository;
        $this->userRepository = $userRepository;
        parent::__construct($params, $userRepository);
    }

    #[Route('/users', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $requestUser = $this->getUserFromJWT($request);
        if (!$this->checkAccess($requestUser,
            [UserRoleEnum::ROLE_COMPANY_ADMIN, UserRoleEnum::ROLE_SUPER_ADMIN])) {
            return $this->responseForbidden();
        }
        $users = $this->userRepository->index($request->get('page', 1));
        return $this->json($users, context: [AbstractNormalizer::GROUPS => ['user:read']]);
    }

    #[Route('/users/{id}', methods: ['GET'])]
    public function show(int $id, Request $request): Response
    {
        $requestUser = $this->getUserFromJWT($request);
        $user = $this->userRepository->findById($id);
        if (
            ($requestUser->isJustUser() and $requestUser->getId() != $id) or
            ($requestUser->isCompanyAdmin() and $requestUser->getCompany() != $user->getCompany()->getId())
        ) {
            return $this->responseForbidden();
        }
        if (!$user) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        return $this->json($user);
    }

    #[Route('/user', methods: ['GET'])]
    public function user(Request $request): Response
    {
        $response = $this->getUserFromJWT($request);
        if (is_array($response)) {
            return $this->json(['error' => $response['message']], $response['status']);
        }
        $user = $response;
        return $this->json($user, context: [AbstractNormalizer::GROUPS => ['user:read']]);
    }

    #[Route('/users', methods: ['POST'])]
    public function store(Request $request, ValidatorInterface $validator): Response
    {
        $requestUser = $this->getUserFromJWT($request);
        if (!$this->checkAccess($requestUser,
            [UserRoleEnum::ROLE_COMPANY_ADMIN, UserRoleEnum::ROLE_SUPER_ADMIN])) {
            return $this->responseForbidden();
        }
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }
        $user = new User();
        if (isset($data['name'])) {
            $user->setName($data['name'] ?? null);
        }
        if (UserRoleEnum::isValid($data['role'] ?? null)) {
            $user->setRole(UserRoleEnum::from($data['role']));
        }
        if (isset($data['company'])) {
            $company = $this->companyRepository->findById($data['company']);
            if (!$company) {
                return $this->json(['message' => 'Company not found'], Response::HTTP_NOT_FOUND);
            }
            $user->setCompany($company);
        }
        if (isset($data['username'])) {
            $user->setUsername($data['username']);
        }
        if (isset($data['password'])) {
            $user->setPassword($data['password']);
        }
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $field = $error->getPropertyPath();
                $errorMessages[$field] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        $this->userRepository->store($user);
        return $this->json(['message' => 'User created successfully'], Response::HTTP_CREATED);
    }

    #[Route('/users/{id}/set-company/{companyId}', methods: ['PUT'])]
    public function setCompany(int $id, int $companyId, Request $request): Response
    {
        $requestUser = $this->getUserFromJWT($request);
        if (!$requestUser->isSuperAdmin()) {
            return $this->responseForbidden();
        }
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

    #[Route('/users', methods: ['PUT'])]
    public function update(int $id, Request $request, ValidatorInterface $validator): Response
    {
        $requestUser = $this->getUserFromJWT($request);
        if (!$this->checkAccess($requestUser,
            [UserRoleEnum::ROLE_COMPANY_ADMIN, UserRoleEnum::ROLE_SUPER_ADMIN])) {
            return $this->responseForbidden();
        }
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }
        $user = $this->userRepository->findById($id);
        if (!$user) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        if ($requestUser->isCompanyAdmin() and
            $requestUser->getCompany()->getId() != $user->getCompany()->getId()) {
            return $this->responseForbidden();
        }
        if (isset($data['name'])) {
            $user->setName($data['name'] ?? null);
        }
        if (UserRoleEnum::isValid($data['role'] ?? null)) {
            $user->setRole(UserRoleEnum::from($data['role']));
        }
        if (isset($data['company'])) {
            $company = $this->companyRepository->findById($data['company']);
            if (!$company) {
                return $this->json(['message' => 'Company not found'], Response::HTTP_NOT_FOUND);
            }
            $user->setCompany($company);
        }
        if (isset($data['username'])) {
            $user->setUsername($data['username']);
        }
        if (isset($data['password'])) {
            $user->setPassword($data['password']);
        }
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $field = $error->getPropertyPath();
                $errorMessages[$field] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        $this->userRepository->update($user);
        return $this->json(['message' => 'User updated successfully']);
    }

    #[Route('/users/{id}/unset-company/{companyId}', methods: ['PUT'])]
    public function unsetCompany(int $id, Request $request): Response
    {
        $requestUser = $this->getUserFromJWT($request);
        if (!$requestUser->isSuperAdmin()) {
            return $this->responseForbidden();
        }
        $user = $this->userRepository->findById($id);
        if (!$user) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        $this->userRepository->unsetCompany($user);
        return $this->json(['message' => 'User was unset to company successfully']);
    }

    #[Route('/users/{id}', methods: ['DELETE'])]
    public function delete(int $id, Request $request): Response
    {
        $requestUser = $this->getUserFromJWT($request);
        if (!$requestUser->isSuperAdmin()) {
            return $this->responseForbidden();
        }
        $user = $this->userRepository->findById($id);
        if (!$user) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }
        $this->userRepository->delete($user);
        return $this->json(['message' => 'User deleted successfully']);
    }
}
