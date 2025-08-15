<?php

namespace App\Application\Ports;

use App\Domain\Entities\Client;

interface ClientRepository
{
    public function findAll(): array;
    
    public function findById(int $id): ?Client;
    
    public function findByEmail(string $email): ?Client;
    
    public function save(Client $client): Client;
    
    public function update(Client $client): Client;
    
    public function delete(int $id): bool;
    
    public function emailExists(string $email, ?int $excludeId = null): bool;

    public function verifyPassword(string $email, string $password): ?array;
}
