<?php

namespace App\Infrastructure\Repository;

use App\Application\Ports\FavoriteProductRepository;
use App\Domain\Entities\FavoriteProduct;
use PDO;
use DateTime;

class FavoriteProductRepositoryImpl implements FavoriteProductRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function findByClientId(int $clientId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM favorite_products 
            WHERE client_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$clientId]);
        $results = $stmt->fetchAll();
        
        return array_map(fn($row) => $this->mapToEntity($row), $results);
    }

    public function findByClientAndProduct(int $clientId, int $productId): ?FavoriteProduct
    {
        $stmt = $this->db->prepare("
            SELECT * FROM favorite_products 
            WHERE client_id = ? AND product_id = ?
        ");
        $stmt->execute([$clientId, $productId]);
        $result = $stmt->fetch();
        
        return $result ? $this->mapToEntity($result) : null;
    }

    public function save(FavoriteProduct $favorite): FavoriteProduct
    {
        $stmt = $this->db->prepare("
            INSERT INTO favorite_products (
                client_id, product_id, product_title, 
                product_image, product_price, product_rating
            ) 
            VALUES (?, ?, ?, ?, ?, ?) 
            RETURNING *
        ");
        
        $stmt->execute([
            $favorite->getClientId(),
            $favorite->getProductId(),
            $favorite->getProductTitle(),
            $favorite->getProductImage(),
            $favorite->getProductPrice(),
            $favorite->getProductRating()
        ]);
        
        $result = $stmt->fetch();
        if (!$result) {
            throw new \Exception('Failed to save favorite product');
        }

        return $this->mapToEntity($result);
    }

    public function delete(int $clientId, int $productId): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM favorite_products 
            WHERE client_id = ? AND product_id = ?
        ");
        return $stmt->execute([$clientId, $productId]);
    }

    public function exists(int $clientId, int $productId): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM favorite_products 
            WHERE client_id = ? AND product_id = ?
        ");
        $stmt->execute([$clientId, $productId]);
        return $stmt->fetchColumn() > 0;
    }

    private function mapToEntity(array $data): FavoriteProduct
    {
        return new FavoriteProduct(
            clientId: $data['client_id'],
            productId: $data['product_id'],
            productTitle: $data['product_title'],
            productImage: $data['product_image'],
            productPrice: (float) $data['product_price'],
            productRating: $data['product_rating'] ? (float) $data['product_rating'] : null,
            id: $data['id'],
            createdAt: new DateTime($data['created_at'])
        );
    }
}
