<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Infrastructure\Http\Controllers\AuthController;
use App\Application\Services\AuthService;
use App\Domain\Entities\Client;
use Mockery;

class AuthControllerTest extends BaseIntegrationTest
{
    private AuthController $controller;
    private AuthService $authServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->authServiceMock = Mockery::mock(AuthService::class);
        $this->controller = new AuthController($this->authServiceMock);
    }

    /**
     * @test
     */
    public function login_with_valid_credentials_should_return_success_response(): void
    {
        $loginData = [
            'email' => 'igor.candido@aiqfome.com.br',
            'password' => '123456'
        ];
        
        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('getId')->andReturn(1);
        $mockClient->shouldReceive('getName')->andReturn('Igor Candido');
        $mockClient->shouldReceive('getEmail')->andReturn('igor.candido@aiqfome.com.br');

        $this->authServiceMock
            ->shouldReceive('authenticateUser')
            ->with('igor.candido@aiqfome.com.br', '123456')
            ->once()
            ->andReturn($mockClient);

        $this->authServiceMock
            ->shouldReceive('generateJWT')
            ->with($mockClient)
            ->once()
            ->andReturn('mocked.jwt.token');

        $request = $this->createJsonRequest('POST', '/auth/login', $loginData);
        $response = $this->createResponse();

        $result = $this->controller->login($request, $response);

        $this->assertResponseStatus($result, 200);
        $this->assertResponseIsJson($result);
        
        $data = $this->getJsonFromResponse($result);

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('access_token', $data['data']);
        $this->assertArrayHasKey('token_type', $data['data']);
        $this->assertArrayHasKey('expires_in', $data['data']);
        $this->assertArrayHasKey('user', $data['data']);
        
        $this->assertEquals('mocked.jwt.token', $data['data']['access_token']);
        $this->assertEquals('Bearer', $data['data']['token_type']);
        $this->assertEquals(86400, $data['data']['expires_in']);
        
        $user = $data['data']['user'];

        $this->assertEquals(1, $user['id']);
        $this->assertEquals('Igor Candido', $user['name']);
        $this->assertEquals('igor.candido@aiqfome.com.br', $user['email']);
    }

    /**
     * @test
     */
    public function login_with_invalid_credentials_should_return_unauthorized(): void
    {
        $loginData = [
            'email' => 'igor.candido@aiqfome.com.br',
            'password' => '123456'
        ];

        $this->authServiceMock
            ->shouldReceive('authenticateUser')
            ->with('igor.candido@aiqfome.com.br', '123456')
            ->once()
            ->andReturn(null);

        $request = $this->createJsonRequest('POST', '/auth/login', $loginData);
        $response = $this->createResponse();

        $result = $this->controller->login($request, $response);

        $this->assertResponseStatus($result, 401);
        $this->assertResponseIsJson($result);
        $this->assertResponseHasError($result, 'Invalid email or password');
    }

    /**
     * @test
     */
    public function login_with_invalid_email_format_should_return_validation_error(): void
    {
        $loginData = [
            'email' => 'invalid-email',
            'password' => '123456'
        ];

        $request = $this->createJsonRequest('POST', '/auth/login', $loginData);
        $response = $this->createResponse();

        $result = $this->controller->login($request, $response);

        $this->assertResponseStatus($result, 400);
        $this->assertResponseIsJson($result);
        $this->assertResponseHasError($result, 'Validation failed: email');
    }

    /**
     * @test
     */
    public function login_with_short_password_should_return_validation_error(): void
    {
        $loginData = [
            'email' => 'igor.candido@aiqfome.com.br',
            'password' => '123'
        ];

        $request = $this->createJsonRequest('POST', '/auth/login', $loginData);
        $response = $this->createResponse();

        $result = $this->controller->login($request, $response);

        $this->assertResponseStatus($result, 400);
        $this->assertResponseIsJson($result);
        $this->assertResponseHasError($result, 'Validation failed: password');
    }

    /**
     * @test
     */
    public function login_with_empty_body_should_return_error(): void
    {
        $request = $this->createJsonRequest('POST', '/auth/login');
        $response = $this->createResponse();

        $result = $this->controller->login($request, $response);

        $this->assertResponseStatus($result, 400);
        $this->assertResponseIsJson($result);
        $this->assertResponseHasError($result, 'Request body is empty');
    }

    /**
     * @test
     */
    public function refresh_token_with_valid_token_should_return_new_token(): void
    {
        $token = 'mocked.jwt.token';
        
        $this->authServiceMock
            ->shouldReceive('refreshToken')
            ->with($token)
            ->once()
            ->andReturn('new.jwt.token');

        $request = $this->createJsonRequest('POST', '/auth/refresh', [], [
            'Authorization' => 'Bearer ' . $token
        ]);
        $response = $this->createResponse();

        $result = $this->controller->refresh($request, $response);

        $this->assertResponseStatus($result, 200);
        $this->assertResponseIsJson($result);
        
        $data = $this->getJsonFromResponse($result);

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('access_token', $data['data']);
        $this->assertArrayHasKey('token_type', $data['data']);
        $this->assertArrayHasKey('expires_in', $data['data']);
        
        $this->assertEquals('new.jwt.token', $data['data']['access_token']);
        $this->assertEquals('Bearer', $data['data']['token_type']);
        $this->assertEquals(86400, $data['data']['expires_in']);
    }

    /**
     * @test
     */
    public function refresh_token_with_invalid_token_should_return_unauthorized(): void
    {
        $token = 'invalid.jwt.token';
        
        $this->authServiceMock
            ->shouldReceive('refreshToken')
            ->with($token)
            ->once()
            ->andReturn(null);

        $request = $this->createJsonRequest('POST', '/auth/refresh', [], [
            'Authorization' => 'Bearer ' . $token
        ]);
        $response = $this->createResponse();

        $result = $this->controller->refresh($request, $response);

        $this->assertResponseStatus($result, 401);
        $this->assertResponseIsJson($result);
        $this->assertResponseHasError($result, 'Invalid or expired token');
    }

    /**
     * @test
     */
    public function refresh_token_without_bearer_token_should_return_bad_request(): void
    {
        $request = $this->createJsonRequest('POST', '/auth/refresh');
        $response = $this->createResponse();

        $result = $this->controller->refresh($request, $response);

        $this->assertResponseStatus($result, 400);
        $this->assertResponseIsJson($result);
        $this->assertResponseHasError($result, 'Bearer token required');
    }

    /**
     * @test
     */
    public function logout_with_valid_token_should_return_success(): void
    {
        $token = 'valid.jwt.token';
        
        $this->authServiceMock
            ->shouldReceive('logout')
            ->with($token)
            ->once()
            ->andReturn(true);

        $request = $this->createJsonRequest('POST', '/auth/logout', [], [
            'Authorization' => 'Bearer ' . $token
        ]);
        $request = $request->withAttribute('token', $token);
        
        $response = $this->createResponse();

        $result = $this->controller->logout($request, $response);

        $this->assertResponseStatus($result, 200);
        $this->assertResponseIsJson($result);
        
        $data = $this->getJsonFromResponse($result);
        
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Logged out successfully', $data['message']);
    }

    /**
     * @test
     */
    public function logout_with_invalid_token_should_return_error(): void
    {
        $token = 'invalid.jwt.token';
        
        $this->authServiceMock
            ->shouldReceive('logout')
            ->with($token)
            ->once()
            ->andReturn(false);

        $request = $this->createJsonRequest('POST', '/auth/logout', [], [
            'Authorization' => 'Bearer ' . $token
        ]);
        $request = $request->withAttribute('token', $token);
        
        $response = $this->createResponse();

        $result = $this->controller->logout($request, $response);

        $this->assertResponseStatus($result, 400);
        $this->assertResponseIsJson($result);
        $this->assertResponseHasError($result, 'Failed to logout. Token may be invalid or already revoked.');
    }

    /**
     * @test
     */
    public function verify_token_with_valid_auth_data_should_return_user_info(): void
    {
        $userData = $this->createSampleUserData();
        
        $request = $this->createJsonRequest('GET', '/auth/verify')
            ->withAttribute('auth_data', $userData);
        $response = $this->createResponse();

        $result = $this->controller->verify($request, $response);

        $this->assertResponseStatus($result, 200);
        $this->assertResponseIsJson($result);
        
        $data = $this->getJsonFromResponse($result);
    
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('user', $data['data']);
        $this->assertArrayHasKey('authenticated', $data['data']);
        
        $this->assertEquals($userData, $data['data']['user']);
        $this->assertTrue($data['data']['authenticated']);
    }
}
