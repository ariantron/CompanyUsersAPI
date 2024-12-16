<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Metadata\ApiProperty;

class LoginRequest
{
    #[Assert\NotBlank]
    #[ApiProperty(
        description: 'Username for authentication',
        openapiContext: [
            'type' => 'string',
            'example' => 'johndoe'
        ]
    )]
    public string $username;

    #[Assert\NotBlank]
    #[ApiProperty(
        description: 'Password for authentication',
        openapiContext: [
            'type' => 'string',
            'example' => 'password123'
        ]
    )]
    public string $password;
}
