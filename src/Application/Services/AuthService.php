<?php

namespace App\Application\Services;

use App\Application\Ports\ClientRepository;
use App\Application\Ports\SessionRepository;
use App\Domain\Entities\Client;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthService
{
    private string $jwtSecret;
    private ClientRepository $clientRepository;
    private SessionRepository $sessionRepository;

    public function __construct(ClientRepository $clientRepository, SessionRepository $sessionRepository)
    {
        $this->jwtSecret = $_ENV['JWT_SECRET'];
        $this->clientRepository = $clientRepository;
        $this->sessionRepository = $sessionRepository;
    }

    public function authenticateUser(string $email, string $password): ?Client
    {
        return $this->clientRepository->verifyPassword($email, $password);
    }

    public function generateJWT(Client $client): string
    {
        $issuedAt = time();
        $expire = $issuedAt + (24 * 60 * 60);
        $jti = bin2hex(random_bytes(16));

        $payload = [
            'iss' => $_ENV['API_BASE_URL'] ?? 'http://localhost:8080',
            'aud' => $_ENV['API_BASE_URL'] ?? 'http://localhost:8080',
            'iat' => $issuedAt,
            'exp' => $expire,
            'jti' => $jti,
            'sub' => $client->getId(),
            'user' => [
                'id' => $client->getId(),
                'name' => $client->getName(),
                'email' => $client->getEmail()
            ]
        ];

        $this->sessionRepository->storeSession($client->getId(), $jti, $expire);
        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    public function validateJWT(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            
            if (isset($decoded->jti) && $this->sessionRepository->isSessionRevoked($decoded->jti)) {
                return null;
            }
            
            return (array) $decoded->user;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function refreshToken(string $token): ?string
    {
        $userData = $this->validateJWT($token);
        if (!$userData) {
            return null;
        }

        $client = $this->clientRepository->findById($userData['id']);
        if (!$client) {
            return null;
        }

        return $this->generateJWT($client);
    }

    public function logout(string $token): bool
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            if (isset($decoded->jti)) {
                return $this->sessionRepository->revokeToken($decoded->jti);
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
