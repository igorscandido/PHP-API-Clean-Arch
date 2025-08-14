<?php

namespace App\Application\Dtos;

class FavoriteProductDto
{
    public function __construct(
        public ?int $id,
        public int $clientId,
        public int $productId,
        public string $productTitle,
        public ?string $productImage = null,
        public ?float $productPrice = null,
        public ?float $productRating = null,
        public ?string $createdAt = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            clientId: $data['client_id'],
            productId: $data['product_id'],
            productTitle: $data['product_title'],
            productImage: $data['product_image'] ?? null,
            productPrice: $data['product_price'] ? (float) $data['product_price'] : null,
            productRating: $data['product_rating'] ? (float) $data['product_rating'] : null,
            createdAt: $data['created_at'] ?? null
        );
    }
}
