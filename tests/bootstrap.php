<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_HOST'] = 'test';
$_ENV['DB_PORT'] = '5432';
$_ENV['DB_NAME'] = 'aiqfome';
$_ENV['DB_USER'] = 'test';
$_ENV['DB_PASSWORD'] = 'test';
$_ENV['JWT_SECRET'] = 'SECR3TK3Y';
$_ENV['REDIS_HOST'] = 'test';
$_ENV['REDIS_PORT'] = '6379';
$_ENV['REDIS_PASSWORD'] = 'test';
$_ENV['API_BASE_URL'] = 'http://localhost:8080';
