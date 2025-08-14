<?php

namespace App\Application\Ports;

interface ProductRepository
{
    public function getProduct(int $productId): ?array;
    public function getAllProducts(): array;
}
