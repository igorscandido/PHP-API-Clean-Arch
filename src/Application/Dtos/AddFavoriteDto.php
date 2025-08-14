<?php

namespace App\Application\Dtos;

class AddFavoriteDto
{
    public function __construct(
        public int $clientId,
        public int $productId
    ) {}

    public static function fromArray(array $data, int $clientId): self
    {
        return new self(
            clientId: $clientId,
            productId: $data['product_id']
        );
    }
}
