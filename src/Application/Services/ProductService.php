<?php

namespace App\Application\Services;

use App\Application\Ports\ProductRepository;

class ProductService
{
    private ProductRepository $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function getAllProducts(): array
    {
        return $this->productRepository->getAllProducts();
    }

    public function getProduct(int $productId): ?array
    {
        if ($productId <= 0) {
            return null;
        }

        return $this->productRepository->getProduct($productId);
    }

    public function productExists(int $productId): bool
    {
        if ($productId <= 0) {
            return false;
        }

        return $this->productRepository->getProduct($productId) !== null;
    }
}
