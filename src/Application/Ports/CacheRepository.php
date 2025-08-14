<?php

namespace App\Application\Ports;

interface CacheRepository
{
    public function set(string $key, mixed $value, ?int $ttl = null): bool;

    public function get(string $key): mixed;

    public function has(string $key): bool;

    public function delete(string $key): bool;

    public function deleteByPattern(string $pattern): int;
}
