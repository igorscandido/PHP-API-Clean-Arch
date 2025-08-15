<?php

namespace App\Application\Services;

use App\Application\Ports\ClientRepository;
use App\Application\Ports\SessionRepository;
use App\Domain\Entities\Client;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use DateTime;

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
        $client = $this->clientRepository->verifyPassword($email, $password);
        if ($client && password_verify($password, $client['password'])) {
            return Client::withoutPassword(
                id: $client['id'],
                name: $client['name'],
                email: $client['email'],
                createdAt: new DateTime($client['created_at']),
                updatedAt: new DateTime($client['updated_at'])
            );
        }

        return null;
    }

    public function generateJWT(Client $client): string
    {
        $issuedAt = time();
        $expire = $issuedAt + (24 * 60 * 60);
        $jti = bin2hex(random_bytes(16));

        $payload = [
            'iss' => $_ENV['API_BASE_URL'],
            'aud' => $_ENV['API_BASE_URL'],
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
        $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
        if (isset($decoded->jti) && !$this->sessionRepository->isSessionRevoked($decoded->jti)) {
            $this->sessionRepository->revokeToken($decoded->jti);
        }

        $client = $this->clientRepository->findById($decoded->user->id);
        if (!$client) {
            return null;
        }

        return $this->generateJWT($client);
    }

    public function logout(string $token): bool
    {
        $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
        if (isset($decoded->jti) && !$this->sessionRepository->isSessionRevoked($decoded->jti)) {
            return $this->sessionRepository->revokeToken($decoded->jti);
        }

        return false;
    }
}
