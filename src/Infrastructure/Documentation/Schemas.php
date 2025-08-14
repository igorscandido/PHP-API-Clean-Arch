<?php

namespace App\Infrastructure\Documentation;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Client',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'id',
            type: 'integer',
            example: 1
        ),
        new OA\Property(
            property: 'name',
            type: 'string',
            example: 'João Silva'
        ),
        new OA\Property(
            property: 'email',
            type: 'string',
            format: 'email',
            example: 'joao.silva@example.com'
        ),
        new OA\Property(
            property: 'created_at',
            type: 'string',
            format: 'date-time',
            example: '2023-01-01T00:00:00Z'
        ),
        new OA\Property(
            property: 'updated_at',
            type: 'string',
            format: 'date-time',
            example: '2023-01-01T00:00:00Z'
        )
    ]
)]
#[OA\Schema(
    schema: 'CreateClientRequest',
    type: 'object',
    required: ['name', 'email', 'password'],
    properties: [
        new OA\Property(
            property: 'name',
            type: 'string',
            example: 'João Silva'
        ),
        new OA\Property(
            property: 'email',
            type: 'string',
            format: 'email',
            example: 'joao.silva@example.com'
        ),
        new OA\Property(
            property: 'password',
            type: 'string',
            format: 'password',
            example: 'password123'
        )
    ]
)]
#[OA\Schema(
    schema: 'UpdateClientRequest',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'name',
            type: 'string',
            example: 'João Silva Updated'
        ),
        new OA\Property(
            property: 'email',
            type: 'string',
            format: 'email',
            example: 'joao.silva.updated@example.com'
        ),
        new OA\Property(
            property: 'password',
            type: 'string',
            format: 'password',
            example: 'newpassword123'
        )
    ]
)]
#[OA\Schema(
    schema: 'GetClientsResponse',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Client')
        ),
        new OA\Property(property: 'total', type: 'integer', example: 10)
    ]
)]
#[OA\Schema(
    schema: 'LoginRequest',
    type: 'object',
    required: ['email', 'password'],
    properties: [
        new OA\Property(
            property: 'email',
            type: 'string',
            format: 'email',
            example: 'igor.candido@aiqfome.com'
        ),
        new OA\Property(
            property: 'password',
            type: 'string',
            format: 'password',
            example: '123456'
        )
    ]
)]
#[OA\Schema(
    schema: 'LoginResponse',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'access_token',
                    type: 'string',
                    example: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...'
                ),
                new OA\Property(
                    property: 'token_type',
                    type: 'string',
                    example: 'Bearer'
                ),
                new OA\Property(
                    property: 'expires_in',
                    type: 'integer',
                    example: 86400
                ),
                new OA\Property(
                    property: 'user',
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'id',
                            type: 'integer',
                            example: 1
                        ),
                        new OA\Property(
                            property: 'name',
                            type: 'string',
                            example: 'Igor Candido'
                        ),
                        new OA\Property(
                            property: 'email',
                            type: 'string',
                            example: 'igor.candido@aiqfome.com'
                        )
                    ]
                )
            ]
        )
    ]
)]
#[OA\Schema(
    schema: 'TokenRefreshResponse',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(property: 'access_token', type: 'string'),
                new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                new OA\Property(property: 'expires_in', type: 'integer', example: 86400)
            ]
        )
    ]
)]
#[OA\Schema(
    schema: 'TokenVerifiedResponse',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'object',
            properties: [
                new OA\Property(property: 'user', ref: '#/components/schemas/Client'),
                new OA\Property(property: 'authenticated', type: 'boolean', example: true)
            ]
        )
    ]
)]
#[OA\Schema(
    schema: 'Product',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'id',
            type: 'integer',
            example: 1
        ),
        new OA\Property(
            property: 'title',
            type: 'string',
            example: 'Fjallraven - Foldsack No. 1 Backpack'
        ),
        new OA\Property(
            property: 'price',
            type: 'number',
            format: 'float',
            example: 109.95
        ),
        new OA\Property(
            property: 'description',
            type: 'string',
            example: 'Your perfect pack for everyday use...'
        ),
        new OA\Property(
            property: 'category',
            type: 'string',
            example: "men's clothing"
        ),
        new OA\Property(
            property: 'image',
            type: 'string',
            example: 'https://fakestoreapi.com/img/81fPKd-2AYL._AC_SL1500_.jpg'
        ),
        new OA\Property(
            property: 'rating',
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'rate',
                    type: 'number',
                    format: 'float',
                    example: 3.9
                ),
                new OA\Property(
                    property: 'count',
                    type: 'integer',
                    example: 120
                )
            ]
        )
    ]
)]
#[OA\Schema(
    schema: 'FavoriteProduct',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'id',
            type: 'integer',
            example: 1
        ),
        new OA\Property(
            property: 'client_id',
            type: 'integer',
            example: 1
        ),
        new OA\Property(
            property: 'product_id',
            type: 'integer',
            example: 1
        ),
        new OA\Property(
            property: 'product_title',
            type: 'string',
            example: 'Fjallraven - Foldsack No. 1 Backpack'
        ),
        new OA\Property(
            property: 'product_image',
            type: 'string',
            nullable: true,
            example: 'https://fakestoreapi.com/img/81fPKd-2AYL._AC_SL1500_.jpg'
        ),
        new OA\Property(
            property: 'product_price',
            type: 'number',
            format: 'float',
            nullable: true,
            example: 109.95
        ),
        new OA\Property(
            property: 'product_rating',
            type: 'number',
            format: 'float',
            nullable: true,
            example: 3.9
        ),
        new OA\Property(
            property: 'created_at',
            type: 'string',
            format: 'date-time',
            example: '2023-01-01T00:00:00Z'
        )
    ]
)]
#[OA\Schema(
    schema: 'AddFavoriteRequest',
    type: 'object',
    required: ['product_id'],
    properties: [
        new OA\Property(
            property: 'product_id',
            type: 'integer',
            example: 1
        )
    ]
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'error',
            type: 'string',
            example: 'Error message'
        )
    ]
)]
#[OA\Schema(
    schema: 'SuccessResponse',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'message',
            type: 'string',
            example: 'Operation completed successfully'
        )
    ]
)]
class Schemas
{}
