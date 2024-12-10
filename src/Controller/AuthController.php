<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AuthController extends AbstractController
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    #[Route('/auth/login', methods: ['POST'])]
    public function login(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;
        if (!$username || !$password) {
            return $this->json(['error' => 'Username and password are required'], 400);
        }
        $token = $this->userRepository->login($username, $password);
        if($token) {
            return $this->json(['token' => $token]);
        }
        return $this->json(['error' => 'Invalid credentials'], 401);
    }
}
