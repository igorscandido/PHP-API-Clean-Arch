<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\Dtos\FavoriteProductDto;
use App\Application\Services\FavoriteService;
use App\Application\Dtos\AddFavoriteDto;
use App\Domain\Entities\FavoriteProduct;
use App\Infrastructure\Http\Controllers\Validators\FavoriteValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Attributes as OA;

class FavoriteController
{
    private FavoriteService $favoriteService;

    public function __construct(FavoriteService $favoriteService)
    {
        $this->favoriteService = $favoriteService;
    }

    #[OA\Get(
        path: '/clients/{clientId}/favorites',
        operationId: 'indexFavorites',
        summary: 'Get client favorite products',
        description: 'Retrieve a list of favorite products for a specific client',
        security: [['bearerAuth' => []]],
        tags: ['Favorites'],
        parameters: [
            new OA\Parameter(
                name: 'clientId',
                description: 'Client ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of favorite products retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/FavoriteProduct')
                        ),
                        new OA\Property(property: 'total', type: 'integer', example: 5)
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Bearer token required or invalid',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Can only access own favorites',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Client not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 500,
                description: 'Failed to fetch favorites',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function index(Request $request, Response $response, array $args): Response
    {
        try {
            $clientId = (int) $args['clientId'];

            if ($request->getAttribute('auth_data')['id'] !== $clientId) {
                return $this->errorResponse($response, 'You are not authorized to access this resource', 403);
            }
            
            $favorites = $this->favoriteService->getClientFavorites($clientId);

            $responseData = array_map(function (FavoriteProduct $favorite) {
                return FavoriteProductDto::fromArray($favorite->toArray());
            }, $favorites);

            $response->getBody()->write(json_encode([
                'data' => $responseData,
                'total' => count($favorites)
            ]));
            
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($response, $e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to fetch favorites', 500);
        }
    }

    #[OA\Post(
        path: '/clients/{clientId}/favorites',
        operationId: 'storeFavorite',
        summary: 'Add product to favorites',
        description: 'Add a product to client\'s favorite list',
        security: [['bearerAuth' => []]],
        tags: ['Favorites'],
        parameters: [
            new OA\Parameter(
                name: 'clientId',
                description: 'Client ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AddFavoriteRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Product added to favorites successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/FavoriteProduct'),
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
                response: 403,
                description: 'Forbidden - Can only add to own favorites',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 400,
                description: 'Validation error or product already in favorites',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict - Product already in favorites',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Client or product not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function store(Request $request, Response $response, array $args): Response
    {
        try {
            $clientId = (int) $args['clientId'];

            if ($request->getAttribute('auth_data')['id'] !== $clientId) {
                return $this->errorResponse($response, 'You are not authorized to access this resource', 403);
            }

            $data = FavoriteValidator::validateAddFavorite($request);
            $addFavoriteDto = AddFavoriteDto::fromArray($data, $clientId);
            
            $favorite = $this->favoriteService->addFavorite($addFavoriteDto);

            $responseData = FavoriteProductDto::fromArray($favorite->toArray());
            
            $response->getBody()->write(json_encode([
                'data' => $responseData,
                'total' => 1
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        } catch (\InvalidArgumentException $e) {
            $status = match ($e->getMessage()) {
                'Client not found' => 404,
                'Product not found in external API' => 404,
                'Product already in favorites' => 409,
                default => 400
            };
            return $this->errorResponse($response, $e->getMessage(), $status);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to add product to favorites', 500);
        }
    }

    #[OA\Delete(
        path: '/clients/{clientId}/favorites/{productId}',
        operationId: 'destroyFavorite',
        summary: 'Remove product from favorites',
        description: 'Remove a product from client\'s favorite list',
        security: [['bearerAuth' => []]],
        tags: ['Favorites'],
        parameters: [
            new OA\Parameter(
                name: 'clientId',
                description: 'Client ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'productId',
                description: 'Product ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product removed from favorites successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized - Bearer token required or invalid',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Can only remove from own favorites',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Client, product, or favorite not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function destroy(Request $request, Response $response, array $args): Response
    {
        try {
            $clientId = (int) $args['clientId'];

            if ($request->getAttribute('auth_data')['id'] !== $clientId) {
                return $this->errorResponse($response, 'You are not authorized to access this resource', 403);
            }

            $productId = (int) $args['productId'];
            
            $deleted = $this->favoriteService->removeFavorite($clientId, $productId);
            if (!$deleted) {
                return $this->errorResponse($response, 'Failed to remove product from favorites', 500);
            }
            
            $response->getBody()->write(json_encode([
                'message' => 'Product successfully removed from favorites'
            ]));
            
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\InvalidArgumentException $e) {
            $status = match ($e->getMessage()) {
                'Client not found' => 404,
                'Product not in favorites' => 404,
                default => 400
            };
            return $this->errorResponse($response, $e->getMessage(), $status);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to remove product from favorites', 500);
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