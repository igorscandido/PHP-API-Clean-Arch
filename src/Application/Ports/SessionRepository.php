<?php

namespace App\Application\Ports;

interface SessionRepository
{
    public function revokeToken(string $jti): bool;
    public function storeSession(int $clientId, string $jti, int $expiresAt): void;
    public function isSessionRevoked(string $jti): bool;
}