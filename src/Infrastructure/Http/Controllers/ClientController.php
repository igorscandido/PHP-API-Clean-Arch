<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\Dtos\ClientDto;
use App\Application\Services\ClientService;
use App\Application\Dtos\CreateClientDto;
use App\Application\Dtos\UpdateClientDto;
use App\Domain\Entities\Client;
use App\Infrastructure\Http\Controllers\Validators\ClientValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Attributes as OA;

class ClientController
{
    private ClientService $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }

    #[OA\Get(
        path: '/clients',
        operationId: 'indexClients',
        summary: 'Get all clients',
        description: 'Retrieve a list of all clients (does not need authentication)',
        tags: ['Clients'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of clients retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/GetClientsResponse')
            ),
            new OA\Response(
                response: 500,
                description: 'Failed to fetch clients',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function index(Request $request, Response $response): Response
    {
        try {
            $clients = $this->clientService->getAllClients();

            $responseData = array_map(function (Client $client) {
                return ClientDto::fromArray($client->toArray())->withoutPassword();
            }, $clients);

            $response->getBody()->write(json_encode([
                'data' => $responseData,
                'total' => count($clients)
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to fetch clients', 500);
        }
    }

    #[OA\Get(
        path: '/clients/{id}',
        operationId: 'showClient',
        summary: 'Get client by ID',
        description: 'Retrieve a specific client by their ID (does not need authentication)',
        tags: ['Clients'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Client ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Client')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Client not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 500,
                description: 'Failed to fetch client',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            $client = $this->clientService->getClientById($id);

            if (!$client) {
                return $this->errorResponse($response, 'Client not found', 404);
            }

            $responseData = ClientDto::fromArray($client->toArray())->withoutPassword();

            $response->getBody()->write(json_encode([
                'data' => $responseData,
                'total' => 1
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to fetch client', 500);
        }
    }

    #[OA\Post(
        path: '/clients',
        operationId: 'storeClient',
        summary: 'Create a new client',
        description: 'Create a new client with the provided information (does not need authentication)',
        tags: ['Clients'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CreateClientRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Client created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Client'),
                        new OA\Property(property: 'total', type: 'integer', example: 1)
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 500,
                description: 'Failed to create client',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function store(Request $request, Response $response): Response
    {
        try {
            $data = ClientValidator::validateCreation($request);
            $createClientDto = CreateClientDto::fromArray($data);

            $client = $this->clientService->createClient($createClientDto);

            $responseData = ClientDto::fromArray($client->toArray())->withoutPassword();
            
            $response->getBody()->write(json_encode([
                'data' => $responseData,
                'total' => 1
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($response, $e->getMessage(), 409);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to create client', 500);
        }
    }

    #[OA\Put(
        path: '/clients/{id}',
        operationId: 'update',
        summary: 'Update a client',
        description: 'Update an existing client with the provided information',
        security: [['bearerAuth' => []]],
        tags: ['Clients'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Client ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateClientRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Client'),
                        new OA\Property(property: 'total', type: 'integer', example: 1)
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Client not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 500,
                description: 'Failed to update client',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];

            if ($request->getAttribute('auth_data')['id'] !== $id) {
                return $this->errorResponse($response, 'You are not authorized to update this client', 403);
            }

            $data = ClientValidator::validateUpdate($request);
            $updateClientDto = UpdateClientDto::fromArray($data);
            
            if (!$updateClientDto->hasData()) {
                return $this->errorResponse($response, 'No data provided for update', 400);
            }
            
            $client = $this->clientService->updateClient($id, $updateClientDto);
            if (!$client) {
                return $this->errorResponse($response, 'Client not found', 404);
            }

            $responseData = ClientDto::fromArray($client->toArray())->withoutPassword();
            
            $response->getBody()->write(json_encode([
                'data' => $responseData,
                'total' => 1
            ]));
            
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($response, $e->getMessage(), 409);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to update client', 500);
        }
    }

    #[OA\Delete(
        path: '/clients/{id}',
        operationId: 'destroyClient',
        summary: 'Delete a client',
        description: 'Delete an existing client',
        security: [['bearerAuth' => []]],
        tags: ['Clients'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Client ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/Client'),
                        new OA\Property(property: 'total', type: 'integer', example: 1)
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Can only delete own account',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Client not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 500,
                description: 'Failed to delete client',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function destroy(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];

            if ($request->getAttribute('auth_data')['id'] !== $id) {
                return $this->errorResponse($response, 'You are not authorized to update this client', 403);
            }

            $client = $this->clientService->getClientById($id);
            if (!$client) {
                return $this->errorResponse($response, 'Client not found', 404);
            }

            $this->clientService->deleteClient($id);

            $responseData = ClientDto::fromArray($client->toArray())->withoutPassword();

            $response->getBody()->write(json_encode([
                'data' => $responseData,
                'total' => 1
            ]));
            
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Failed to delete client', 500);
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