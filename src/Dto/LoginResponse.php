<?php

namespace App\Dto;

class LoginResponse
{
    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }
}
