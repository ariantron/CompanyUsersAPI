<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\JwtService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

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

    protected function getUserFromJWT(Request $request): ?User
    {
        $authorizationHeader = $request->headers->get('Authorization');
        if (!$authorizationHeader) {
            throw new UnauthorizedHttpException('No authorization token provided');
        }
        $token = str_replace('Bearer ', '', $authorizationHeader);
        $userData = (new JWTService($this->params))->decodeJWT($token);
        if($userData) {
            return $this->userRepository->findById($userData['id']);
        }
        return null;
    }
}