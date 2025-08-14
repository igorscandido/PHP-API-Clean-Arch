<?php

namespace App\Infrastructure\Documentation;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    openapi: '3.0.0',
    info: new OA\Info(
        version: '1.0.0',
        title: 'Aiqfome Challenge API',
        description: 'RESTful API para gerenciamento de produtos favoritos dos clientes.',
    ),
    servers: [
        new OA\Server(
            url: 'http://localhost:8080/api/v1',
            description: 'Version 1'
        )
    ]
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'JWT Authorization header using the Bearer scheme. Example: "Authorization: Bearer {token}"'
)]
#[OA\Tag(
    name: 'Authentication',
    description: 'Authentication endpoints'
)]
#[OA\Tag(
    name: 'Clients',
    description: 'Client management endpoints'
)]
#[OA\Tag(
    name: 'Products',
    description: 'Product management endpoints'
)]
#[OA\Tag(
    name: 'Favorites',
    description: 'Favorite products management endpoints'
)]
class OpenApiSpec
{}
