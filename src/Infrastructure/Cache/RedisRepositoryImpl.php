<?php

namespace App\Infrastructure\Cache;

use App\Application\Ports\CacheRepository;
use Predis\Client;

class RedisRepositoryImpl implements CacheRepository
{
    private Client $redis;
    private int $defaultTtl;

    public function __construct(Client $redis)
    {
        $this->redis = $redis;

        $this->defaultTtl = (int) $_ENV['CACHE_DEFAULT_TTL'];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        try {
            $serializedValue = $this->serialize($value);
            $actualTtl = $ttl ?? $this->defaultTtl;
            
            if ($actualTtl > 0) {
                return (string) $this->redis->setex($key, $actualTtl, $serializedValue) === 'OK';
            } else {
                return (string) $this->redis->set($key, $serializedValue) === 'OK';
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function get(string $key): mixed
    {
        try {
            $value = $this->redis->get($key);
            if ($value === null) {
                return null;
            }

            return $this->unserialize($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function has(string $key): bool
    {
        try {
            return $this->redis->exists($key) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function delete(string $key): bool
    {
        try {
            return $this->redis->del($key) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function deleteByPattern(string $pattern): int
    {
        try {
            $keys = $this->redis->keys($pattern);
            if (empty($keys)) {
                return 0;
            }
            
            return $this->redis->del($keys);
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function serialize(mixed $value): string
    {
        return serialize($value);
    }

    private function unserialize(string $value): mixed
    {
        return unserialize($value);
    }
}
