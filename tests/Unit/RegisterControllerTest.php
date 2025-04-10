<?php

namespace Tests\Unit;

use App\Http\Controllers\AuthController;
use App\Http\Requests\AuthRegisterRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Auth\AuthManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Mockery;
use Tests\TestCase;

class RegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    protected AuthController $authController;
    protected AuthService $authService;
    protected $auth;

    public function setUp(): void
    {
        parent::setUp();
        
        // Mock AuthService
        $this->authService = Mockery::mock(AuthService::class);
        
        // Mock Auth để kiểm soát trạng thái đăng nhập
        $this->auth = Mockery::mock(AuthManager::class);
        
        // Chỉ đạo Laravel container trả về mock auth khi yêu cầu
        $this->app->instance('auth', $this->auth);
        
        // Tạo controller với service được mock
        $this->authController = new AuthController($this->authService);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test đăng ký người dùng thành công khi admin đăng nhập
     *
     * @return void
     */
    public function testRegisterSuccessWithAdminRole()
    {
        // 1. Arrange - Chuẩn bị dữ liệu
        $adminUser = new User([
            'id' => 1,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin'
        ]);
        
        // Giả lập người dùng đã đăng nhập với quyền admin
        $this->auth->shouldReceive('check')
            ->once()
            ->andReturn(true);
            
        $this->auth->shouldReceive('user')
            ->once()
            ->andReturn($adminUser);
        
        // Dữ liệu đăng ký người dùng mới
        $newUserData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'role' => 'student'
        ];
        
        // Giả lập AuthRegisterRequest
        $request = Mockery::mock(AuthRegisterRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($newUserData);
        
        // Giả lập kết quả từ AuthService
        $expectedServiceResult = [
            'id' => 2,
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'role' => 'student'
        ];
        
        // Thiết lập AuthService::register() trả về kết quả giả lập
        $this->authService->shouldReceive('register')
            ->once()
            ->with($newUserData, $adminUser)
            ->andReturn($expectedServiceResult);

        // 2. Act - Gọi phương thức register
        $response = $this->authController->register($request);

        // 3. Assert - Kiểm tra kết quả
        // Kiểm tra kiểu trả về
        $this->assertInstanceOf(JsonResponse::class, $response);
        
        // Kiểm tra status code (201 Created)
        $this->assertEquals(201, $response->getStatusCode());
        
        // Kiểm tra cấu trúc JSON
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals('Đăng ký người dùng thành công', $responseData['message']);
        $this->assertEquals($expectedServiceResult, $responseData['data']);
    }

    /**
     * Test đăng ký người dùng khi chưa đăng nhập
     *
     * @return void
     */
    public function testRegisterWhenNotLoggedIn()
    {
        // 1. Arrange - Chuẩn bị dữ liệu
        // Giả lập người dùng chưa đăng nhập
        $this->auth->shouldReceive('check')
            ->once()
            ->andReturn(false);
        
        // Dữ liệu đăng ký người dùng mới
        $newUserData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123'
        ];
        
        // Giả lập AuthRegisterRequest
        $request = Mockery::mock(AuthRegisterRequest::class);
        $request->shouldReceive('validated')
            ->never();

        // Không gọi service vì chưa đăng nhập
        $this->authService->shouldReceive('register')
            ->never();

        // 2. Act - Gọi phương thức register
        $response = $this->authController->register($request);

        // 3. Assert - Kiểm tra kết quả
        // Kiểm tra kiểu trả về
        $this->assertInstanceOf(JsonResponse::class, $response);
        
        // Kiểm tra status code (401 Unauthorized)
        $this->assertEquals(401, $response->getStatusCode());
        
        // Kiểm tra cấu trúc JSON
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Vui lòng đăng nhập để tiếp tục', $responseData['message']);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertArrayHasKey('auth', $responseData['errors']);
    }

    /**
     * Test đăng ký người dùng khi đăng nhập với quyền không phải admin
     *
     * @return void
     */
    public function testRegisterWithNonAdminRole()
    {
        // 1. Arrange - Chuẩn bị dữ liệu
        $nonAdminUser = new User([
            'id' => 2,
            'name' => 'Student User',
            'email' => 'student@example.com',
            'role' => 'student'
        ]);
        
        // Giả lập người dùng đã đăng nhập nhưng không phải admin
        $this->auth->shouldReceive('check')
            ->once()
            ->andReturn(true);
            
        $this->auth->shouldReceive('user')
            ->once()
            ->andReturn($nonAdminUser);
        
        // Dữ liệu đăng ký người dùng mới
        $newUserData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123'
        ];
        
        // Giả lập AuthRegisterRequest
        $request = Mockery::mock(AuthRegisterRequest::class);
        $request->shouldReceive('validated')
            ->never();

        // Không gọi service vì không có quyền admin
        $this->authService->shouldReceive('register')
            ->never();

        // 2. Act - Gọi phương thức register
        $response = $this->authController->register($request);

        // 3. Assert - Kiểm tra kết quả
        // Kiểm tra kiểu trả về
        $this->assertInstanceOf(JsonResponse::class, $response);
        
        // Kiểm tra status code (403 Forbidden)
        $this->assertEquals(403, $response->getStatusCode());
        
        // Kiểm tra cấu trúc JSON
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Bạn không có quyền thực hiện hành động này', $responseData['message']);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertArrayHasKey('permission', $responseData['errors']);
    }
}
