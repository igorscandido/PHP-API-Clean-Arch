<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Infrastructure\Http\Controllers\FavoriteController;
use App\Application\Services\FavoriteService;
use App\Application\Dtos\AddFavoriteDto;
use App\Domain\Entities\FavoriteProduct;
use Mockery;

class FavoriteControllerTest extends BaseIntegrationTest
{
    private FavoriteController $controller;
    private FavoriteService $favoriteServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->favoriteServiceMock = Mockery::mock(FavoriteService::class);
        $this->controller = new FavoriteController($this->favoriteServiceMock);
    }

    /**
     * @test
     */
    public function index_with_valid_client_should_return_favorites_list(): void
    {
        $clientId = 1;
        $userData = $this->createSampleUserData();
        
        $mockFavorite1 = Mockery::mock(FavoriteProduct::class);
        $mockFavorite1->shouldReceive('toArray')->andReturn([
            'id' => 1,
            'client_id' => 1,
            'product_id' => 1,
            'product_title' => 'Product 1',
            'product_image' => 'image1.jpg',
            'product_price' => 29.99,
            'product_rating' => 4.5,
            'created_at' => '2023-08-14 13:00:00'
        ]);

        $mockFavorite2 = Mockery::mock(FavoriteProduct::class);
        $mockFavorite2->shouldReceive('toArray')->andReturn([
            'id' => 2,
            'client_id' => 1,
            'product_id' => 2,
            'product_title' => 'Product 2',
            'product_image' => 'image2.jpg',
            'product_price' => 39.99,
            'product_rating' => 4.0,
            'created_at' => '2023-08-14 13:00:00'
        ]);

        $favorites = [$mockFavorite1, $mockFavorite2];

        $this->favoriteServiceMock
            ->shouldReceive('getClientFavorites')
            ->with($clientId)
            ->once()
            ->andReturn($favorites);

        $request = $this->createJsonRequest('GET', "/clients/{$clientId}/favorites")
            ->withAttribute('auth_data', $userData);
        $response = $this->createResponse();

        $result = $this->controller->index($request, $response, ['clientId' => (string)$clientId]);

        $this->assertResponseStatus($result, 200);
        $this->assertResponseIsJson($result);
        
        $data = $this->getJsonFromResponse($result);

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertEquals(2, $data['total']);
        $this->assertCount(2, $data['data']);
    }

    /**
     * @test
     */
    public function index_with_unauthorized_client_should_return_forbidden(): void
    {
        $clientId = 1;
        $userData = ['id' => 2, 'name' => 'Joao', 'email' => 'joao@aiqfome.com.br'];

        $request = $this->createJsonRequest('GET', "/clients/{$clientId}/favorites")
            ->withAttribute('auth_data', $userData);
        $response = $this->createResponse();

        $result = $this->controller->index($request, $response, ['clientId' => (string)$clientId]);

        $this->assertResponseStatus($result, 403);
        $this->assertResponseIsJson($result);
        $this->assertResponseHasError($result, 'You are not authorized to access this resource');
    }

    /**
     * @test
     */
    public function index_with_non_existent_client_should_return_not_found(): void
    {
        $clientId = 999;
        $userData = ['id' => 999, 'name' => 'Joao', 'email' => 'joao@aiqfome.com.br'];

        $this->favoriteServiceMock
            ->shouldReceive('getClientFavorites')
            ->with($clientId)
            ->once()
            ->andThrow(new \InvalidArgumentException('Client not found'));

        $request = $this->createJsonRequest('GET', "/clients/{$clientId}/favorites")
            ->withAttribute('auth_data', $userData);
        $response = $this->createResponse();

        $result = $this->controller->index($request, $response, ['clientId' => (string)$clientId]);

        $this->assertResponseStatus($result, 404);
        $this->assertResponseIsJson($result);
        $this->assertResponseHasError($result, 'Client not found');
    }

    /**
     * @test
     */
    public function store_with_valid_data_should_add_favorite_successfully(): void
    {
        $clientId = 1;
        $userData = $this->createSampleUserData();
        $requestData = ['product_id' => 1];

        $mockFavorite = Mockery::mock(FavoriteProduct::class);
        $mockFavorite->shouldReceive('toArray')->andReturn($this->createSampleFavoriteData());

        $this->favoriteServiceMock
            ->shouldReceive('addFavorite')
            ->with(Mockery::on(function (AddFavoriteDto $dto) use ($clientId) {
                return $dto->clientId === $clientId && $dto->productId === 1;
            }))
            ->once()
            ->andReturn($mockFavorite);

        $request = $this->createJsonRequest('POST', "/clients/{$clientId}/favorites", $requestData)
            ->withAttribute('auth_data', $userData);
        $response = $this->createResponse();

        $result = $this->controller->store($request, $response, ['clientId' => (string) $clientId]);

        $this->assertResponseStatus($result, 201);
        $this->assertResponseIsJson($result);
        
        $data = $this->getJsonFromResponse($result);
    
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertEquals(1, $data['total']);
    }

    /**
     * @test
     */
    public function store_with_unauthorized_client_should_return_forbidden(): void
    {
        $clientId = 1;
        $userData = ['id' => 2, 'name' => 'Joao', 'email' => 'joao@aiqfome.com.br'];
        $requestData = ['product_id' => 1];

        $request = $this->createJsonRequest('POST', "/clients/{$clientId}/favorites", $requestData)
            ->withAttribute('auth_data', $userData);
        $response = $this->createResponse();

        $result = $this->controller->store($request, $response, ['clientId' => (string) $clientId]);

        $this->assertResponseStatus($result, 403);
        $this->assertResponseIsJson($result);
        $this->assertResponseHasError($result, 'You are not authorized to access this resource');
    }

    /**
     * @test
     */
    public function store_with_invalid_product_id_should_return_validation_error(): void
    {
        $clientId = 1;
        $userData = $this->createSampleUserData();
        $requestData = ['product_id' => 'invalid'];

        $request = $this->createJsonRequest('POST', "/clients/{$clientId}/favorites", $requestData)
            ->withAttribute('auth_data', $userData);
        $response = $this->createResponse();

        $result = $this->controller->store($request, $response, ['clientId' => (string) $clientId]);

        $this->assertResponseStatus($result, 400);
        $this->assertResponseIsJson($result);
        $this->assertResponseHasError($result, 'product_id must be positive');
    }

    /**
     * @test
     */
    public function store_with_non_existent_client_should_return_not_found(): void
    {
        $clientId = 999;
        $userData = ['id' => 999, 'name' => 'User', 'email' => 'user@test.com'];
        $requestData = ['product_id' => 1];

        $this->favoriteServiceMock
            ->shouldReceive('addFavorite')
            ->once()
            ->andThrow(new \InvalidArgumentException('Client not found'));

        $request = $this->createJsonRequest('POST', "/clients/{$clientId}/favorites", $requestData)
            ->withAttribute('auth_data', $userData);
        $response = $this->createResponse();

        $result = $this->controller->store($request, $response, ['clientId' => (string) $clientId]);

        $this->assertResponseStatus($result, 404);
        $this->assertResponseIsJson($result);
        $this->assertResponseHasError($result, 'Client not found');
    }

    /**
     * @test
     */
    public function store_with_non_existent_product_should_return_not_found(): void
    {
        $clientId = 1;
        $userData = $this->createSampleUserData();
        $requestData = ['product_id' => 999];

        $this->favoriteServiceMock
            ->shouldReceive('addFavorite')
            ->once()
            ->andThrow(new \InvalidArgumentException('Product not found in external API'));

        $request = $this->createJsonRequest('POST', "/clients/{$clientId}/favorites", $requestData)
            ->withAttribute('auth_data', $userData);
        $response = $this->createResponse();

        $result = $this->controller->store($request, $response, ['clientId' => (string)$clientId]);

        $this->assertResponseStatus($result, 404);
        $this->assertResponseIsJson($result);
        $this->assertResponseHasError($result, 'Product not found in external API');
    }

    /**
     * @test
     */
    public function store_with_already_favorited_product_should_return_conflict(): void
    {
        $clientId = 1;
        $userData = $this->createSampleUserData();
        $requestData = ['product_id' => 1];

        $this->favoriteServiceMock
            ->shouldReceive('addFavorite')
            ->once()
            ->andThrow(new \InvalidArgumentException('Product already in favorites'));

        $request = $this->createJsonRequest('POST', "/clients/{$clientId}/favorites", $requestData)
            ->withAttribute('auth_data', $userData);
        $response = $this->createResponse();

        $result = $this->controller->store($request, $response, ['clientId' => (string)$clientId]);

        $this->assertResponseStatus($result, 409);
        $this->assertResponseIsJson($result);
        $this->assertResponseHasError($result, 'Product already in favorites');
    }

    /**
     * @test
     */
    public function destroy_with_valid_data_should_remove_favorite_successfully(): void
    {
        $clientId = 1;
        $productId = 1;
        $userData = $this->createSampleUserData();

        $this->favoriteServiceMock
            ->shouldReceive('removeFavorite')
            ->with($clientId, $productId)
            ->once()
            ->andReturn(true);

        $request = $this->createJsonRequest('DELETE', "/clients/{$clientId}/favorites/{$productId}")
            ->withAttribute('auth_data', $userData);
        $response = $this->createResponse();

        $result = $this->controller->destroy(
            $request, 
            $response, 
            ['clientId' => (string) $clientId, 'productId' => (string) $productId]
        );

        $this->assertResponseStatus($result, 200);
        $this->assertResponseIsJson($result);
        
        $data = $this->getJsonFromResponse($result);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Product successfully removed from favorites', $data['message']);
    }

    /**
     * @test
     */
    public function destroy_with_unauthorized_client_should_return_forbidden(): void
    {
        $clientId = 1;
        $productId = 1;
        $userData = ['id' => 2, 'name' => 'Joao', 'email' => 'joao@aiqfome.com.br'];

        $request = $this->createJsonRequest('DELETE', "/clients/{$clientId}/favorites/{$productId}")
            ->withAttribute('auth_data', $userData);
        $response = $this->createResponse();

        $result = $this->controller->destroy(
            $request, 
            $response, 
            ['clientId' => (string) $clientId, 'productId' => (string) $productId]
        );

        $this->assertResponseStatus($result, 403);
        $this->assertResponseIsJson($result);
        $this->assertResponseHasError($result, 'You are not authorized to access this resource');
    }

    /**
     * @test
     */
    public function destroy_with_non_existent_client_should_return_not_found(): void
    {
        $clientId = 999;
        $productId = 1;
        $userData = ['id' => 999, 'name' => 'Joao', 'email' => 'joao@aiqfome.com.br'];

        $this->favoriteServiceMock
            ->shouldReceive('removeFavorite')
            ->with($clientId, $productId)
            ->once()
            ->andThrow(new \InvalidArgumentException('Client not found'));

        $request = $this->createJsonRequest('DELETE', "/clients/{$clientId}/favorites/{$productId}")
            ->withAttribute('auth_data', $userData);
        $response = $this->createResponse();

        $result = $this->controller->destroy(
            $request, 
            $response, 
            ['clientId' => (string) $clientId, 'productId' => (string) $productId]
        );

        $this->assertResponseStatus($result, 404);
        $this->assertResponseIsJson($result);
        $this->assertResponseHasError($result, 'Client not found');
    }

    /**
     * @test
     */
    public function destroy_with_non_favorited_product_should_return_not_found(): void
    {
        $clientId = 1;
        $productId = 999;
        $userData = $this->createSampleUserData();

        $this->favoriteServiceMock
            ->shouldReceive('removeFavorite')
            ->with($clientId, $productId)
            ->once()
            ->andThrow(new \InvalidArgumentException('Product not in favorites'));

        $request = $this->createJsonRequest('DELETE', "/clients/{$clientId}/favorites/{$productId}")
            ->withAttribute('auth_data', $userData);
        $response = $this->createResponse();

        $result = $this->controller->destroy(
            $request, 
            $response, 
            ['clientId' => (string) $clientId, 'productId' => (string)$productId]
        );

        $this->assertResponseStatus($result, 404);
        $this->assertResponseIsJson($result);
        $this->assertResponseHasError($result, 'Product not in favorites');
    }

    /**
     * @test
     */
    public function destroy_when_removal_fails_should_return_server_error(): void
    {
        $clientId = 1;
        $productId = 1;
        $userData = $this->createSampleUserData();

        $this->favoriteServiceMock
            ->shouldReceive('removeFavorite')
            ->with($clientId, $productId)
            ->once()
            ->andReturn(false);

        $request = $this->createJsonRequest('DELETE', "/clients/{$clientId}/favorites/{$productId}")
            ->withAttribute('auth_data', $userData);
        $response = $this->createResponse();

        $result = $this->controller->destroy(
            $request, 
            $response, 
            ['clientId' => (string)$clientId, 'productId' => (string)$productId]
        );

        $this->assertResponseStatus($result, 500);
        $this->assertResponseIsJson($result);
        $this->assertResponseHasError($result, 'Failed to remove product from favorites');
    }

    /**
     * @test
     */
    public function store_with_missing_product_id_should_return_validation_error(): void
    {
        $clientId = 1;
        $userData = $this->createSampleUserData();
        $requestData = [];

        $request = $this->createJsonRequest('POST', "/clients/{$clientId}/favorites", $requestData)
            ->withAttribute('auth_data', $userData);
        $response = $this->createResponse();

        $result = $this->controller->store($request, $response, ['clientId' => (string) $clientId]);

        $this->assertResponseStatus($result, 400);
        $this->assertResponseIsJson($result);
        $this->assertResponseHasError($result, 'Request body is empty');
    }

    /**
     * @test
     */
    public function store_with_negative_product_id_should_return_validation_error(): void
    {
        $clientId = 1;
        $userData = $this->createSampleUserData();
        $requestData = ['product_id' => -1];

        $request = $this->createJsonRequest('POST', "/clients/{$clientId}/favorites", $requestData)
            ->withAttribute('auth_data', $userData);
        $response = $this->createResponse();

        $result = $this->controller->store($request, $response, ['clientId' => (string)$clientId]);

        $this->assertResponseStatus($result, 400);
        $this->assertResponseIsJson($result);
        $this->assertResponseHasError($result, 'product_id must be positive');
    }
}
