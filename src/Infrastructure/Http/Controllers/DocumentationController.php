<?php

namespace App\Infrastructure\Http\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Generator;

class DocumentationController
{
    public function getOpenApiSpecJson(Request $request, Response $response): Response
    {
        try {
            $openapi = Generator::scan([
                __DIR__ . '/../../../',
            ]);
            
            $jsonSpec = $openapi->toJson();
            $response->getBody()->write($jsonSpec);

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to generate OpenAPI specification: ' . $e->getMessage()
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
    
    public function getSwaggerUIHtmlPage(Request $request, Response $response): Response
    {
        $html = file_get_contents(__DIR__ . '/../../../../public/swagger-ui.html');
        
        $response->getBody()->write($html);
        return $response
            ->withHeader('Content-Type', 'text/html')
            ->withStatus(200);
    }
}
