<?php

namespace App\Controller;

use App\Entity\Company;
use App\Repository\CompanyRepository;
use App\Repository\UserRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CompanyController extends BaseController
{
    private ParameterBagInterface $params;
    private CompanyRepository $companyRepository;
    private UserRepository $userRepository;

    public function __construct(
        ParameterBagInterface $params,
        CompanyRepository $companyRepository,
        UserRepository $userRepository
    )
    {
        $this->params = $params;
        $this->companyRepository = $companyRepository;
        $this->userRepository = $userRepository;
        parent::__construct($params, $userRepository);
    }

    #[Route('/companies', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $filters = [
            'pageNumber' => $request->query->getInt('page_number', 1),
            'pageSize' => $request->query->getInt('page_size', 12),
        ];

        $companies = $this->companyRepository->index($filters);

        return $this->json($companies);
    }

    #[Route('/companies/{id}', methods: ['GET'])]
    public function show(int $id): Response
    {
        $company = $this->companyRepository->findById($id);

        if (!$company) {
            return $this->json(['error' => 'Company not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($company);
    }

    #[Route('/companies/{id}/users', methods: ['GET'])]
    public function getUsers(int $id): Response
    {
        $company = $this->companyRepository->findById($id);

        if (!$company) {
            return $this->json(['error' => 'Company not found'], Response::HTTP_NOT_FOUND);
        }

        $users = $this->companyRepository->getUsers($company);

        return $this->json($users);
    }

    /**
     * @Route("/company", name="company_create", methods={"POST"})
     */
    #[Route('/companies', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): Response
    {
        $company = new Company();
        $company->setName($request->get('name'));

        $errors = $validator->validate($company);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->companyRepository->store($company);
            return $this->json(['message' => 'Company created successfully'], Response::HTTP_CREATED);
        } catch (Exception) {
            return $this->json(['error' => 'An error occurred while creating the company'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/companies/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request, ValidatorInterface $validator): Response
    {
        $company = $this->companyRepository->findById($id);

        if (!$company) {
            return $this->json(['error' => 'Company not found'], Response::HTTP_NOT_FOUND);
        }

        $company->setName($request->get('name'));

        $errors = $validator->validate($company);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->companyRepository->update($company);
            return $this->json(['message' => 'Company updated successfully']);
        } catch (Exception) {
            return $this->json(['error' => 'An error occurred while updating the company'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/companies/{id}', methods: ['DELETE'])]
    public function delete(int $id): Response
    {
        $company = $this->companyRepository->findById($id);

        if (!$company) {
            return $this->json(['error' => 'Company not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->companyRepository->delete($company);
            return $this->json(['message' => 'Company deleted successfully']);
        } catch (Exception) {
            return $this->json(['error' => 'An error occurred while deleting the company'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
