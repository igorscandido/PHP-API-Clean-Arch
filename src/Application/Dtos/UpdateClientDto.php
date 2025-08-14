<?php

namespace App\Application\Dtos;

class UpdateClientDto
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $password = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            email: $data['email'] ?? null,
            password: $data['password'] ?? null
        );
    }

    public function hasData(): bool
    {
        return $this->name !== null || $this->email !== null || $this->password !== null;
    }
}
