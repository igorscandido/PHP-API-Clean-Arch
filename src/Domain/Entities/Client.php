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
        ?string $password = null,
        ?int $id = null,
        ?DateTime $createdAt = null,
        ?DateTime $updatedAt = null
    ) {
        $this->validateName($name);
        $this->validateEmail($email);

        if ($password) {
            $this->validatePassword($password);
        }

        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->createdAt = $createdAt ?? new DateTime();
        $this->updatedAt = $updatedAt ?? new DateTime();
    }

    public static function create(string $name, string $email, string $password): self
    {
        return new self($name, $email, $password);
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
        $this->validateName($name);
        $this->name = $name;
        $this->updatedAt = new DateTime();
    }

    public function updateEmail(string $email): void
    {
        $this->validateEmail($email);
        $this->email = $email;
        $this->updatedAt = new DateTime();
    }

    public function updatePassword(string $password): void
    {
        if (!$password) {
            throw new \InvalidArgumentException('Password cannot be empty');
        }

        $this->validatePassword($password);
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->updatedAt = new DateTime();
    }

    public function verifyPassword(string $password): bool
    {
        if (!$this->password) {
            return false;
        }

        return password_verify($password, $this->password);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s')
        ];
    }

    private function validateName(string $name): void
    {
        if (empty(trim($name))) {
            throw new \InvalidArgumentException('Name cannot be empty');
        }

        if (strlen($name) > 255) {
            throw new \InvalidArgumentException('Name cannot exceed 255 characters');
        }
    }

    private function validateEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }

        if (strlen($email) > 255) {
            throw new \InvalidArgumentException('Email cannot exceed 255 characters');
        }
    }

    private function validatePassword(string $password): void
    {
        if (strlen($password) < 6) {
            throw new \InvalidArgumentException('Password must be at least 6 characters long');
        }
    }
}
