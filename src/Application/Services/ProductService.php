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
        try {
            return $this->productRepository->getAllProducts();
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch products: ' . $e->getMessage());
        }
    }

    public function getProduct(int $productId): ?array
    {
        if ($productId <= 0) {
            return null;
        }

        try {
            return $this->productRepository->getProduct($productId);
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch product: ' . $e->getMessage());
        }
    }

    public function productExists(int $productId): bool
    {
        if ($productId <= 0) {
            return false;
        }

        try {
            return $this->productRepository->getProduct($productId) !== null;
        } catch (\Exception $e) {
            throw new \Exception('Failed to validate product: ' . $e->getMessage());
        }
    }
}
