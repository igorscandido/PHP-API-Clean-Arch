<?php

namespace App\Infrastructure\Repository;

use App\Application\Ports\SessionRepository;
use PDO;

class SessionRepositoryImpl implements SessionRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function revokeToken(string $jti): bool
    {
        $stmt = $this->db->prepare("
            UPDATE user_sessions 
            SET revoked_at = CURRENT_TIMESTAMP 
            WHERE jti = ?
        ");
        return $stmt->execute([$jti]);
    }

    public function storeSession(int $clientId, string $jti, int $expiresAt): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO user_sessions (client_id, jti, expires_at) 
            VALUES (?, ?, ?) 
            ON CONFLICT (jti) DO UPDATE SET 
                expires_at = EXCLUDED.expires_at
        ");
        $stmt->execute([$clientId, $jti, date('Y-m-d H:i:s', $expiresAt)]);
    }

    public function isSessionRevoked(string $jti): bool
    {
        $stmt = $this->db->prepare("
            SELECT revoked_at FROM user_sessions 
            WHERE jti = ? AND expires_at > CURRENT_TIMESTAMP
        ");
        $stmt->execute([$jti]);

        $result = $stmt->fetch();
        if (!$result) {
            return true;
        }
        
        return $result['revoked_at'] !== null;
    }
}
