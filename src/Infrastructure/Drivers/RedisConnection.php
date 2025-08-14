<?php

namespace App\Infrastructure\Drivers;

use Predis\Client;

class RedisConnection
{
    private static ?Client $connection = null;

    public static function getConnection(): Client
    {
        if (self::$connection === null) {
            self::$connection = new Client([
            'scheme' => 'tcp',
            'host' => $_ENV['REDIS_HOST'],
            'port' => (int) ($_ENV['REDIS_PORT']),
            'password' => $_ENV['REDIS_PASSWORD'],
            ]);
        }

        return self::$connection;
    }
}
