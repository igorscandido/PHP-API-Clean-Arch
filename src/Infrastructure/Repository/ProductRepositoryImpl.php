<?php

namespace App\Infrastructure\Repository;

use App\Application\Ports\ProductRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ProductRepositoryImpl implements ProductRepository
{
    private Client $httpClient;
    private string $baseUrl;

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 10,
            'connect_timeout' => 5,
        ]);

        $this->baseUrl = $_ENV['PRODUCT_API_URL'];
    }

    public function getProduct(int $productId): ?array
    {
        try {
            $response = $this->httpClient->get("{$this->baseUrl}/products/{$productId}");
            
            if ($response->getStatusCode() === 200) {
                $productData = json_decode($response->getBody()->getContents(), true);
                if (!$productData) {
                    return null;
                }
                
                return [
                    'id' => $productData['id'],
                    'title' => $productData['title'],
                    'image' => $productData['image'] ?? null,
                    'price' => $productData['price'] ?? null,
                    'rating' => $productData['rating']['rate'] ?? null,
                    'description' => $productData['description'] ?? null,
                    'category' => $productData['category'] ?? null,
                ];
            }
        } catch (GuzzleException $e) {
            throw new \Exception('Failed to fetch product: ' . $e->getMessage());
        }

        return null;
    }

    public function getAllProducts(): array
    {
        try {
            $response = $this->httpClient->get("{$this->baseUrl}/products");
            
            if ($response->getStatusCode() === 200) {
                $products = json_decode($response->getBody()->getContents(), true);
                
                return array_map(function ($product) {
                    return [
                        'id' => $product['id'],
                        'title' => $product['title'],
                        'image' => $product['image'] ?? null,
                        'price' => $product['price'] ?? null,
                        'rating' => $product['rating']['rate'] ?? null,
                        'description' => $product['description'] ?? null,
                        'category' => $product['category'] ?? null,
                    ];
                }, $products);
            }
        } catch (GuzzleException $e) {
            throw new \Exception('Failed to fetch products: ' . $e->getMessage());
        }

        return [];
    }
}
