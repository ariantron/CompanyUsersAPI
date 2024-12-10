<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\JwtService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BaseController extends AbstractController
{
    private ParameterBagInterface $params;
    private UserRepository $userRepository;

    /**
     * @param ParameterBagInterface $params
     * @param UserRepository $userRepository
     */
    public function __construct(ParameterBagInterface $params, UserRepository $userRepository)
    {
        $this->params = $params;
        $this->userRepository = $userRepository;
    }

    protected function getUserFromJWT(Request $request): User|array
    {
        $authorizationHeader = $request->headers->get('Authorization');
        if (!$authorizationHeader) {
            return [
                'status' => Response::HTTP_UNAUTHORIZED,
                'message' => 'Authorization header not found'
            ];
        }
        $token = str_replace('Bearer ', '', $authorizationHeader);
        $userData = (new JWTService($this->params))->decodeJWT($token);
        if ($userData) {
            $user = $this->userRepository->findById($userData['id']);
            if ($user) {
                return $user;
            }
            return [
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'User not found'
            ];
        }
        return [
            'status' => Response::HTTP_UNAUTHORIZED,
            'message' => 'Token is invalid'
        ];
    }

    protected function responseForbidden(): Response
    {
        return $this->json(['error' => 'Access Denied!'], Response::HTTP_FORBIDDEN);
    }

    protected function checkAccess(User $user, array $rules): bool
    {
        if (in_array($user->getRole(), $rules)) {
            return true;
        }
        return false;
    }
}