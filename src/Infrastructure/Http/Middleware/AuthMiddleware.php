<?php

namespace App\Infrastructure\Http\Middleware;

use App\Application\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorizedResponse('Bearer token required');
        }
        
        $token = substr($authHeader, 7);
        $userData = $this->authService->validateJWT($token);
        if (!$userData) {
            return $this->unauthorizedResponse('Invalid or expired token');
        }
        
        $request = $request
            ->withAttribute('auth_data', $userData)
            ->withAttribute('token', $token);
        
        return $handler->handle($request);
    }

    private function unauthorizedResponse(string $message): Response
    {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode([
            'error' => $message
        ]));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
}