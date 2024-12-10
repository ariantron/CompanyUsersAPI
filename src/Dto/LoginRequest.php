<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class LoginRequest
{
    #[Assert\NotBlank]
    public string $username;

    #[Assert\NotBlank]
    public string $password;
}