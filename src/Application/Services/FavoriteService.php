<?php

namespace App\Application\Services;

use App\Application\Ports\ClientRepository;
use App\Application\Ports\FavoriteProductRepository;
use App\Application\Ports\ProductRepository;
use App\Application\Dtos\AddFavoriteDto;
use App\Domain\Entities\FavoriteProduct;

class FavoriteService
{
    private ClientRepository $clientRepository;
    private FavoriteProductRepository $favoriteRepository;
    private ProductRepository $productRepository;

    public function __construct(
        ClientRepository $clientRepository,
        FavoriteProductRepository $favoriteRepository,
        ProductRepository $productRepository
    ) {
        $this->clientRepository = $clientRepository;
        $this->favoriteRepository = $favoriteRepository;
        $this->productRepository = $productRepository;
    }

    public function getClientFavorites(int $clientId): array
    {
        $client = $this->clientRepository->findById($clientId);
        if (!$client) {
            throw new \InvalidArgumentException('Client not found');
        }

        return $this->favoriteRepository->findByClientId($clientId);
    }

    public function addFavorite(AddFavoriteDto $addFavoriteDto): FavoriteProduct
    {
        $client = $this->clientRepository->findById($addFavoriteDto->clientId);
        if (!$client) {
            throw new \InvalidArgumentException('Client not found');
        }

        if ($this->favoriteRepository->exists($addFavoriteDto->clientId, $addFavoriteDto->productId)) {
            throw new \InvalidArgumentException('Product already in favorites');
        }

        $productData = $this->productRepository->getProduct($addFavoriteDto->productId);
        if (!$productData) {
            throw new \InvalidArgumentException('Product not found in external API');
        }

        $favorite = FavoriteProduct::create(
            $addFavoriteDto->clientId,
            $addFavoriteDto->productId,
            $productData['title'],
            $productData['image'],
            $productData['price'],
            $productData['rating']
        );

        return $this->favoriteRepository->save($favorite);
    }

    public function removeFavorite(int $clientId, int $productId): bool
    {
        $client = $this->clientRepository->findById($clientId);
        if (!$client) {
            throw new \InvalidArgumentException('Client not found');
        }

        if (!$this->favoriteRepository->exists($clientId, $productId)) {
            throw new \InvalidArgumentException('Product not in favorites');
        }

        return $this->favoriteRepository->delete($clientId, $productId);
    }
}
