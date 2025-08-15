<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\Services\ProductService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Attributes as OA;

class ProductController
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    #[OA\Get(
        path: '/products',
        operationId: 'indexProducts',
        summary: 'Get all products',
        description: 'Retrieve a list of all available products from external API',
        security: [['bearerAuth' => []]],
        tags: ['Products'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of products retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Product')
                        ),
                        new OA\Property(property: 'total', type: 'integer', example: 10)
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Bearer token required or invalid',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 500,
                description: 'Failed to fetch products',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function index(Request $request, Response $response): Response
    {
        try {
            $products = $this->productService->getAllProducts();
            
            $response->getBody()->write(json_encode([
                'data' => $products,
                'total' => count($products)
            ]));
            
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to fetch products', 500);
        }
    }

    #[OA\Get(
        path: '/products/{id}',
        operationId: 'showProduct',
        summary: 'Get product by ID',
        description: 'Retrieve a specific product by its ID from external API',
        security: [['bearerAuth' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Product ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Product'),
                        new OA\Property(property: 'total', type: 'integer', example: 1)
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Bearer token required or invalid',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Product not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 500,
                description: 'Failed to fetch product',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];

            $product = $this->productService->getProduct($id);
            if (!$product) {
                return $this->errorResponse($response, 'Product not found', 404);
            }
            
            $response->getBody()->write(json_encode([
                'data' => $product,
                'total' => 1
            ]));
            
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to fetch product', 500);
        }
    }

    private function errorResponse(Response $response, string $message, int $status = 400): Response
    {
        $response->getBody()->write(json_encode([
            'error' => $message
        ]));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}