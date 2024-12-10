<?php

namespace App\Service;

use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class JwtService
{
    private const string AUDIENCE = 'app';

    private ParameterBagInterface $params;
    private string $issuer;
    private string $secretKey;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
        $this->issuer = $this->params->get('app.domain');
        $this->secretKey = $this->params->get('jwt.secret_key');
    }

    /**
     * Generate a JWT token for a given user.
     */
    public function generateJWT(array $user): string
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600; // 1 hour expiration
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'iss' => $this->issuer,
            'aud' => self::AUDIENCE,
            'data' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ]
        ];

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    /**
     * Decode and validate a JWT token.
     */
    public function decodeJWT(string $jwt): ?array
    {
        try {
            $decoded = JWT::decode($jwt, new Key($this->secretKey, 'HS256'));
            return (array) $decoded->data;
        } catch (Exception) {
            return null;
        }
    }
}
