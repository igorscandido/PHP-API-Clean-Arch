<?php

namespace App\Domain\Entities;

use DateTime;

class Client
{
    private ?int $id;
    private string $name;
    private string $email;
    private ?string $password;
    private DateTime $createdAt;
    private DateTime $updatedAt;

    public function __construct(
        string $name,
        string $email,
        string $password,
        ?int $id = null,
        ?DateTime $createdAt = null,
        ?DateTime $updatedAt = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->createdAt = $createdAt ?? new DateTime();
        $this->updatedAt = $updatedAt ?? new DateTime();
    }

    public static function withoutPassword(int $id, string $name, string $email, DateTime $createdAt, DateTime $updatedAt): self
    {
        $reflection = new \ReflectionClass(self::class);
        $client = $reflection->newInstanceWithoutConstructor();

        $client->id = $id;
        $client->name = $name;
        $client->email = $email;
        $client->createdAt = $createdAt;
        $client->updatedAt = $updatedAt;

        return $client;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            id: $data['id'] ?? null,
            createdAt: isset($data['created_at']) ? new DateTime($data['created_at']) : null,
            updatedAt: isset($data['updated_at']) ? new DateTime($data['updated_at']) : null
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function updateName(string $name): void
    {
        $this->name = $name;
        $this->updatedAt = new DateTime();
    }

    public function updateEmail(string $email): void
    {
        $this->email = $email;
        $this->updatedAt = new DateTime();
    }

    public function updatePassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->updatedAt = new DateTime();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->createdAt->format('d/m/Y H:i:s'),
            'updated_at' => $this->updatedAt->format('d/m/Y H:i:s')
        ];
    }
}
