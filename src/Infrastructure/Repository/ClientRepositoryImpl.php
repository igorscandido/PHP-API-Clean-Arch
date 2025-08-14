<?php

namespace App\Infrastructure\Repository;

use App\Application\Ports\ClientRepository;
use App\Domain\Entities\Client;
use PDO;
use DateTime;

class ClientRepositoryImpl implements ClientRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findAll(): array
    {
        $stmt = $this->db->query("
            SELECT id, name, email, created_at, updated_at 
            FROM clients 
            ORDER BY created_at DESC
        ");
        $results = $stmt->fetchAll();

        return array_map(fn($row) => $this->mapToEntity($row), $results);
    }

    public function findById(int $id): ?Client
    {
        $stmt = $this->db->prepare("
            SELECT id, name, email, password, created_at, updated_at 
            FROM clients 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        $result = $stmt->fetch();

        return $result ? $this->mapToEntity($result) : null;
    }

    public function findByEmail(string $email): ?Client
    {
        $stmt = $this->db->prepare("
            SELECT id, name, email, password, created_at, updated_at 
            FROM clients 
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        
        return $result ? $this->mapToEntity($result) : null;
    }

    public function save(Client $client): Client
    {        
        $stmt = $this->db->prepare("
            INSERT INTO clients (name, email, password) 
            VALUES (?, ?, ?) 
            RETURNING id, name, email, password, created_at, updated_at
        ");
        $stmt->execute([
            $client->getName(),
            $client->getEmail(),
            $client->getPassword()
        ]);

        $result = $stmt->fetch();
        if (!$result) {
            throw new \Exception('Failed to create client');
        }

        return $this->mapToEntity($result);
    }

    public function update(Client $client): Client
    {
        $stmt = $this->db->prepare("
            UPDATE clients 
            SET name = ?, email = ?, password = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ? 
            RETURNING id, name, email, password, created_at, updated_at
        ");
        
        $stmt->execute([
            $client->getName(),
            $client->getEmail(),
            $client->getPassword(),
            $client->getId()
        ]);
        
        $result = $stmt->fetch();
        if (!$result) {
            throw new \Exception('Failed to update client');
        }

        return $this->mapToEntity($result);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM clients WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM clients WHERE email = ?";
        $params = [$email];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() > 0;
    }

    public function verifyPassword(string $email, string $password): ?Client
    {
        $client = $this->findByEmail($email);
        if ($client && $client->verifyPassword($password)) {
            return $client;
        }
        
        return null;
    }

    private function mapToEntity(array $data): Client
    {
        if (!isset($data['password'])) {
            return Client::withoutPassword(
                id: $data['id'],
                name: $data['name'],
                email: $data['email'],
                createdAt: isset($data['created_at']) ? new DateTime($data['created_at']) : new DateTime(),
                updatedAt: isset($data['updated_at']) ? new DateTime($data['updated_at']) : new DateTime()
            );
        }

        return new Client(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            id: $data['id'],
            createdAt: new DateTime($data['created_at']),
            updatedAt: new DateTime($data['updated_at'])
        );
    }
}
