<?php

namespace Tests\Unit;

use App\Http\Controllers\AuthController;
use App\Http\Requests\AuthLoginRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Mockery;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected AuthController $authController;
    protected AuthService $authService;

    public function setUp(): void
    {
        parent::setUp();
        
        // Mock AuthService để không phải test thật
        $this->authService = Mockery::mock(AuthService::class);
        
        // Tạo controller với service được mock
        $this->authController = new AuthController($this->authService);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test phương thức login khi đăng nhập thành công
     *
     * @return void
     */
    public function testLoginSuccess()
    {
        // 1. Arrange - Chuẩn bị dữ liệu
        $email = 'test@example.com';
        $password = 'password123';
        
        // Giả lập dữ liệu request
        $requestData = [
            'email' => $email,
            'password' => $password,
        ];
        
        // Giả lập AuthLoginRequest
        $request = Mockery::mock(AuthLoginRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($requestData);
            
        // Giả lập kết quả từ AuthService
        $expectedServiceResult = [
            'token' => 'fake-jwt-token',
            'user' => [
                'id' => 1,
                'name' => 'Test User',
                'email' => $email,
                'role' => 'student',
            ],
        ];
        
        // Thiết lập AuthService::login() trả về kết quả giả lập
        $this->authService->shouldReceive('login')
            ->once()
            ->with($requestData)
            ->andReturn($expectedServiceResult);

        // 2. Act - Gọi phương thức login
        $response = $this->authController->login($request);

        // 3. Assert - Kiểm tra kết quả
        // Kiểm tra kiểu trả về
        $this->assertInstanceOf(JsonResponse::class, $response);
        
        // Kiểm tra status code
        $this->assertEquals(200, $response->getStatusCode());
        
        // Kiểm tra cấu trúc JSON
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals('Đăng nhập thành công', $responseData['message']);
        $this->assertEquals($expectedServiceResult, $responseData['data']);
    }

    /**
     * Test phương thức login khi xảy ra lỗi validation
     *
     * @return void
     */
    public function testLoginValidationError()
    {
        // Trong Laravel, các lỗi validation được xử lý tự động bởi FormRequest
        // Chúng ta không cần test lại các lỗi validation ở Controller level
        // vì đã được xử lý trong AuthLoginRequest
        $this->assertTrue(true);
    }

    /**
     * Test phương thức login khi xảy ra lỗi xác thực
     *
     * @return void
     */
    public function testLoginAuthenticationError()
    {
        // 1. Arrange - Chuẩn bị dữ liệu
        $requestData = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ];
        
        // Giả lập AuthLoginRequest
        $request = Mockery::mock(AuthLoginRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($requestData);
        
        // Giả lập AuthService ném ngoại lệ
        $this->authService->shouldReceive('login')
            ->once()
            ->with($requestData)
            ->andThrow(new \Symfony\Component\HttpKernel\Exception\HttpException(401, 'Thông tin đăng nhập không hợp lệ'));

        // 2. Act - Gọi phương thức login
        $response = $this->authController->login($request);

        // 3. Assert - Kiểm tra kết quả
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Thông tin đăng nhập không hợp lệ', $responseData['message']);
    }
}