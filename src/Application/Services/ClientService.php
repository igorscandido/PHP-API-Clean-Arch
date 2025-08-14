<?php

namespace App\Application\Services;

use App\Application\Ports\ClientRepository;
use App\Application\Dtos\CreateClientDto;
use App\Application\Dtos\UpdateClientDto;
use App\Domain\Entities\Client;

class ClientService
{
    private ClientRepository $clientRepository;

    public function __construct(ClientRepository $clientRepository)
    {
        $this->clientRepository = $clientRepository;
    }

    public function getAllClients(): array
    {
        return $this->clientRepository->findAll();
    }

    public function getClientById(int $id): ?Client
    {
        return $this->clientRepository->findById($id);
    }

    public function createClient(CreateClientDto $createClientDto): Client
    {
        if ($this->clientRepository->emailExists($createClientDto->email)) {
            throw new \InvalidArgumentException('Email already exists');
        }

        $client = Client::create(
            $createClientDto->name,
            $createClientDto->email,
            $createClientDto->password
        );
        
        return $this->clientRepository->save($client);
    }

    public function updateClient(int $id, UpdateClientDto $updateClientDto): ?Client
    {
        $client = $this->clientRepository->findById($id);
        if (!$client) {
            return null;
        }

        if ($updateClientDto->email && $this->clientRepository->emailExists($updateClientDto->email, $id)) {
            throw new \InvalidArgumentException('Email already exists');
        }

        if ($updateClientDto->name) {
            $client->updateName($updateClientDto->name);
        }

        if ($updateClientDto->email) {
            $client->updateEmail($updateClientDto->email);
        }

        if ($updateClientDto->password) {
            $client->updatePassword($updateClientDto->password);
        }
        
        return $this->clientRepository->update($client);
    }

    public function deleteClient(int $id): bool
    {
        return $this->clientRepository->delete($id);
    }
}
