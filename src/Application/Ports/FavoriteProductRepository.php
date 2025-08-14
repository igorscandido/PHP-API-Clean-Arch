<?php

namespace App\Application\Ports;

use App\Domain\Entities\FavoriteProduct;

interface FavoriteProductRepository
{
    public function findByClientId(int $clientId): array;
    public function findByClientAndProduct(int $clientId, int $productId): ?FavoriteProduct;
    public function save(FavoriteProduct $favorite): FavoriteProduct;
    public function delete(int $clientId, int $productId): bool;
    public function exists(int $clientId, int $productId): bool;
}
