<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\ResponseFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Mockery;

/**
 * @abstract
 */
abstract class BaseIntegrationTest extends TestCase
{
    protected ServerRequestFactory $requestFactory;
    protected ResponseFactory $responseFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->requestFactory = new ServerRequestFactory();
        $this->responseFactory = new ResponseFactory();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function createJsonRequest(
        string $method,
        string $uri,
        array $data = [],
        array $headers = []
    ): ServerRequestInterface {
        $request = $this->requestFactory->createServerRequest($method, $uri);
        
        if (!empty($data)) {
            $request->getBody()->write(json_encode($data));
            $request->getBody()->rewind();
            $request = $request->withHeader('Content-Type', 'application/json');
        }

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request;
    }

    protected function createResponse(): ResponseInterface
    {
        return $this->responseFactory->createResponse();
    }

    protected function getJsonFromResponse(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);
        
        $this->assertIsArray($decoded, 'Response body should be valid JSON');
        return $decoded;
    }

    protected function assertResponseStatus(ResponseInterface $response, int $expectedStatus): void
    {
        $this->assertEquals(
            $expectedStatus,
            $response->getStatusCode(),
            sprintf(
                'Expected status %d but got %d. Response body: %s',
                $expectedStatus,
                $response->getStatusCode(),
                (string) $response->getBody()
            )
        );
    }

    protected function assertResponseIsJson(ResponseInterface $response): void
    {
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
    }

    protected function assertResponseHasError(ResponseInterface $response, string $expectedMessage = null): void
    {
        $data = $this->getJsonFromResponse($response);
        $this->assertArrayHasKey('error', $data);
        
        if ($expectedMessage !== null) {
            $this->assertStringContainsString($expectedMessage, $data['error']);
        }
    }

    protected function assertResponseHasData(ResponseInterface $response): void
    {
        $data = $this->getJsonFromResponse($response);
        $this->assertArrayHasKey('data', $data);
    }

    protected function createSampleUserData(): array
    {
        return [
            'id' => 1,
            'name' => 'Igor Candido',
            'email' => 'igor.candido@aiqfome.com.br'
        ];
    }

    protected function createSampleFavoriteData(): array
    {
        return [
            'id' => 1,
            'client_id' => 1,
            'product_id' => 1,
            'product_title' => 'Test Product',
            'product_image' => 'https://example.com/image.jpg',
            'product_price' => 29.99,
            'product_rating' => 4.5,
            'created_at' => '2025-08-14 13:00:00'
        ];
    }
}
