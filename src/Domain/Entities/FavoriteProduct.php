<?php

namespace App\Domain\Entities;

use DateTime;

class FavoriteProduct
{
    private ?int $id;
    private int $clientId;
    private int $productId;
    private string $productTitle;
    private string $productImage;
    private float $productPrice;
    private ?float $productRating;
    private DateTime $createdAt;

    public function __construct(
        int $clientId,
        int $productId,
        string $productTitle,
        string $productImage,
        float $productPrice,
        ?float $productRating = null,
        ?int $id = null,
        ?DateTime $createdAt = null
    ) {
        $this->validateClientId($clientId);
        $this->validateProductId($productId);
        $this->validateProductTitle($productTitle);
        $this->validateProductRating($productRating);
        
        $this->id = $id;
        $this->clientId = $clientId;
        $this->productId = $productId;
        $this->productTitle = $productTitle;
        $this->productImage = $productImage;
        $this->productPrice = $productPrice;
        $this->productRating = $productRating;
        $this->createdAt = $createdAt ?? new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientId(): int
    {
        return $this->clientId;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getProductTitle(): string
    {
        return $this->productTitle;
    }

    public function getProductImage(): ?string
    {
        return $this->productImage;
    }

    public function getProductPrice(): ?float
    {
        return $this->productPrice;
    }

    public function getProductRating(): ?float
    {
        return $this->productRating;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function updateProductTitle(string $title): void
    {
        $this->validateProductTitle($title);
        $this->productTitle = $title;
    }

    public function updateProductImage(?string $image): void
    {
        $this->productImage = $image;
    }

    public function updateProductPrice(?float $price): void
    {
        $this->productPrice = $price;
    }

    public function updateProductRating(?float $rating): void
    {
        $this->validateProductRating($rating);
        $this->productRating = $rating;
    }

    private function validateClientId(int $clientId): void
    {
        if ($clientId <= 0) {
            throw new \InvalidArgumentException('Client ID must be a positive integer');
        }
    }

    private function validateProductId(int $productId): void
    {
        if ($productId <= 0) {
            throw new \InvalidArgumentException('Product ID must be a positive integer');
        }
    }

    private function validateProductTitle(string $title): void
    {
        if (empty(trim($title))) {
            throw new \InvalidArgumentException('Product title cannot be empty');
        }

        if (strlen($title) > 500) {
            throw new \InvalidArgumentException('Product title cannot exceed 500 characters');
        }
    }

    private function validateProductRating(?float $rating): void
    {
        if ($rating !== null && ($rating < 0 || $rating > 5)) {
            throw new \InvalidArgumentException('Product rating must be between 0 and 5');
        }
    }

    public static function fromArray(array $data): self
    {
        return new self(
            clientId: $data['client_id'],
            productId: $data['product_id'],
            productTitle: $data['product_title'],
            productImage: $data['product_image'] ?? null,
            productPrice: isset($data['product_price']) ? (float) $data['product_price'] : null,
            productRating: isset($data['product_rating']) ? (float) $data['product_rating'] : null,
            id: $data['id'] ?? null,
            createdAt: isset($data['created_at']) ? new DateTime($data['created_at']) : null
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->clientId,
            'product_id' => $this->productId,
            'product_title' => $this->productTitle,
            'product_image' => $this->productImage,
            'product_price' => $this->productPrice,
            'product_rating' => $this->productRating,
            'created_at' => $this->createdAt->format('d/m/Y H:i:s')
        ];
    }
}
