<?php

namespace App\Infrastructure\Repository;

use App\Application\Ports\FavoriteProductRepository;
use App\Application\Ports\CacheRepository;
use App\Domain\Entities\FavoriteProduct;

class CachedFavoriteProductRepository implements FavoriteProductRepository
{
    private FavoriteProductRepository $repository;
    private CacheRepository $cache;
    private int $cacheTtl;

    public function __construct(
        FavoriteProductRepository $repository,
        CacheRepository $cache
    ) {
        $this->repository = $repository;
        $this->cache = $cache;
        $this->cacheTtl = (int) ($_ENV['FAVORITES_CACHE_TTL'] ?? 1800);
    }

    public function findByClientId(int $clientId): array
    {
        $cacheKey = $this->getCacheKey('client', $clientId);
        
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $this->unserializeFavorites($cached);
        }
        
        $favorites = $this->repository->findByClientId($clientId);
        if (!empty($favorites)) {
            $this->cache->set($cacheKey, $this->serializeFavorites($favorites), $this->cacheTtl);
        }
        
        return $favorites;
    }

    public function findByClientAndProduct(int $clientId, int $productId): ?FavoriteProduct
    {
        $cacheKey = $this->getCacheKey('client_product', $clientId, $productId);

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return $cached ? FavoriteProduct::fromArray($cached) : null;
        }
        
        $favorite = $this->repository->findByClientAndProduct($clientId, $productId);
    
        $this->cache->set(
            $cacheKey, 
            $favorite ? $favorite->toArray() : false, 
            $this->cacheTtl
        );
        
        return $favorite;
    }

    public function save(FavoriteProduct $favorite): FavoriteProduct
    {
        $savedFavorite = $this->repository->save($favorite);
        
        $this->invalidateCacheForClient($savedFavorite->getClientId());
        return $savedFavorite;
    }

    public function delete(int $clientId, int $productId): bool
    {
        $result = $this->repository->delete($clientId, $productId);
        if ($result) {
            $this->invalidateCacheForClient($clientId);
            $this->cache->delete($this->getCacheKey('client_product', $clientId, $productId));
        }
        
        return $result;
    }

    public function exists(int $clientId, int $productId): bool
    {
        $cacheKey = $this->getCacheKey('exists', $clientId, $productId);

        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            return (bool) $cached;
        }

        $exists = $this->repository->exists($clientId, $productId);
        $this->cache->set($cacheKey, $exists, min($this->cacheTtl, 300));
        return $exists;
    }

    private function invalidateCacheForClient(int $clientId): void
    {
        $this->cache->delete($this->getCacheKey('client', $clientId));
        
        $this->cache->deleteByPattern("favorites:client_product:{$clientId}:*");
        $this->cache->deleteByPattern("favorites:exists:{$clientId}:*");
    }

    private function getCacheKey(string $type, int $clientId, ?int $productId = null): string
    {
        $key = "favorites:{$type}:{$clientId}";
        
        if ($productId !== null) {
            $key .= ":{$productId}";
        }
        
        return $key;
    }

    private function serializeFavorites(array $favorites): array
    {
        return array_map(function (FavoriteProduct $favorite) {
            return $favorite->toArray();
        }, $favorites);
    }

    private function unserializeFavorites(array $cachedData): array
    {
        return array_map(function (array $favoriteData) {
            return FavoriteProduct::fromArray($favoriteData);
        }, $cachedData);
    }
}
