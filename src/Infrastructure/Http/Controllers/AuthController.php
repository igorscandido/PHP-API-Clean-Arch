<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\Services\AuthService;
use App\Infrastructure\Http\Controllers\Validators\AuthValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Attributes as OA;

class AuthController
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    #[OA\Post(
        path: '/auth/login',
        operationId: 'login',
        summary: 'Client login',
        description: 'Authenticate client with email and password',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/LoginRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful login',
                content: new OA\JsonContent(ref: '#/components/schemas/LoginResponse')
            ),
            new OA\Response(
                response: 400,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid credentials',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 500,
                description: 'Login failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function login(Request $request, Response $response): Response
    {
        try {
            $data = AuthValidator::validateLogin($request);
            $email = $data['email'];
            $password = $data['password'];
            
            $client = $this->authService->authenticateUser($email, $password);
            if (!$client) {
                return $this->errorResponse($response, 'Invalid email or password', 401);
            }
            
            $jwt = $this->authService->generateJWT($client);
            
            $response->getBody()->write(json_encode([
                'data' => [
                    'access_token' => $jwt,
                    'token_type' => 'Bearer',
                    'expires_in' => 86400,
                    'user' => [
                        'id' => $client->getId(),
                        'name' => $client->getName(),
                        'email' => $client->getEmail()
                    ]
                ]
            ]));
            
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($response, $e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Login failed', 500);
        }
    }

    #[OA\Post(
        path: '/auth/refresh',
        operationId: 'refresh',
        summary: 'Refresh JWT token',
        description: 'Refresh an existing JWT token',
        security: [['bearerAuth' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token refreshed successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/TokenRefreshResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid or expired token',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 500,
                description: 'Token refresh failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function refresh(Request $request, Response $response): Response
    {
        try {
            $authHeader = $request->getHeaderLine('Authorization');
            if (!str_starts_with($authHeader, 'Bearer ')) {
                return $this->errorResponse($response, 'Bearer token required', 400);
            }
            
            $token = substr($authHeader, 7);

            $newToken = $this->authService->refreshToken($token);
            if (!$newToken) {
                return $this->errorResponse($response, 'Invalid or expired token', 401);
            }
            
            $response->getBody()->write(json_encode([
                'data' => [
                    'access_token' => $newToken,
                    'token_type' => 'Bearer',
                    'expires_in' => 86400
                ]
            ]));
            
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Token refresh failed', 500);
        }
    }

    #[OA\Post(
        path: '/auth/logout',
        operationId: 'logout',
        summary: 'Client logout',
        description: 'Logout client and invalidate JWT token',
        security: [['bearerAuth' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logged out successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/SuccessResponse')
            ),
            new OA\Response(
                response: 400,
                description: 'Failed to logout',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid authorization header',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            ),
            new OA\Response(
                response: 500,
                description: 'Logout failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function logout(Request $request, Response $response): Response
    {
        try {            
            $token = $request->getAttribute('token');

            $logoutSuccess = $this->authService->logout($token);
            if (!$logoutSuccess) {
                return $this->errorResponse($response, 'Failed to logout. Token may be invalid or already revoked.', 400);
            }

            $response->getBody()->write(json_encode([
                'message' => 'Logged out successfully'
            ]));
            
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            return $this->errorResponse($response, 'Logout failed', 500);
        }
    }

    #[OA\Get(
        path: '/auth/verify',
        operationId: 'verify',
        summary: 'Verify JWT token',
        description: 'Verify the current JWT token and return client information',
        security: [['bearerAuth' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token verified successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/TokenVerifiedResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid or expired token',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function verify(Request $request, Response $response): Response
    {
        $authData = $request->getAttribute('auth_data');
        
        $response->getBody()->write(json_encode([
            'data' => [
                'user' => $authData,
                'authenticated' => true
            ]
        ]));
        
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
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