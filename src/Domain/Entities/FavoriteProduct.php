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

    public static function fromArray(array $data): self
    {
        return new self(
            clientId: $data['client_id'],
            productId: $data['product_id'],
            productTitle: $data['product_title'],
            productImage: $data['product_image'],
            productPrice: (float) $data['product_price'],
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
