<?php

namespace Tests\Unit;

use App\Models\User; 
use App\Services\AuthService; 
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuthService $authService;

    public function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
    }

    /**
     * Test đăng nhập thành công
     *
     * @return void
     */
    public function testLoginSuccessfully()
    {
        // 1. Arrange - Chuẩn bị dữ liệu
        $email = 'test@example.com';
        $password = 'password123';
        
        // Tạo một user mẫu cho việc testing
        $user = User::factory()->create([
            'email' => $email,
            'password' => Hash::make($password),
            'role' => User::ROLE_STUDENT,
        ]);

        // 2. Act - Thực hiện hành động
        $result = $this->authService->login([
            'email' => $email,
            'password' => $password,
        ]);

        // 3. Assert - Kiểm tra kết quả
        // Kiểm tra cấu trúc của kết quả trả về
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('user', $result);
        
        // Kiểm tra thông tin user trả về có đúng không
        $this->assertEquals($user->id, $result['user']['id']);
        $this->assertEquals($user->name, $result['user']['name']);
        $this->assertEquals($user->email, $result['user']['email']);
        $this->assertEquals($user->role, $result['user']['role']);
        
        // Kiểm tra token có hợp lệ không
        $this->assertNotEmpty($result['token']);
    }

    /**
     * Test đăng nhập với email không tồn tại
     *
     * @return void
     */
    public function testLoginWithNonExistentEmail()
    {
        // 1. Arrange - Email không tồn tại trong hệ thống
        $credentials = [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ];

        // 2 & 3. Act & Assert - Thực hiện và kiểm tra kết quả
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Thông tin đăng nhập không hợp lệ');
        
        $this->authService->login($credentials);
    }

    /**
     * Test đăng nhập với mật khẩu sai
     *
     * @return void
     */
    public function testLoginWithIncorrectPassword()
    {
        // 1. Arrange - Tạo user nhưng sử dụng mật khẩu sai khi đăng nhập
        $email = 'test@example.com';
        $correctPassword = 'correctpassword';
        $incorrectPassword = 'wrongpassword';
        
        User::factory()->create([
            'email' => $email,
            'password' => Hash::make($correctPassword),
        ]);

        // 2 & 3. Act & Assert - Thực hiện và kiểm tra kết quả
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Thông tin đăng nhập không hợp lệ');
        
        $this->authService->login([
            'email' => $email,
            'password' => $incorrectPassword,
        ]);
    }

    /**
     * Test đăng nhập với dữ liệu không hợp lệ (thiếu email)
     *
     * @return void
     */
    public function testLoginWithInvalidData()
    {
        // 1. Arrange - Chuẩn bị dữ liệu thiếu email
        $credentials = [
            'password' => 'password123',
            // thiếu email
        ];

        // 2 & 3. Act & Assert - Thực hiện và kiểm tra kết quả
        $this->expectException(ValidationException::class);
        
        $this->authService->login($credentials);
    }

    /**
     * Test đăng nhập với email không đúng định dạng
     *
     * @return void
     */
    public function testLoginWithInvalidEmail()
    {
        // 1. Arrange - Email không đúng định dạng
        $credentials = [
            'email' => 'invalid-email',
            'password' => 'password123',
        ];

        // 2 & 3. Act & Assert - Thực hiện và kiểm tra kết quả
        $this->expectException(ValidationException::class);
        
        $this->authService->login($credentials);
    }
    
/**
     * Test đăng nhập không mật khẩu
     * 
     */
    
    /**
     * Test đăng xuất thành công
     *
     * @return void
     */
    public function testLogoutSuccessfully()
    {
        // 1. Arrange - Chuẩn bị mock cho JWT
        $token = 'valid_token';
        
        JWTAuth::shouldReceive('getToken')
            ->once()
            ->andReturn($token);
            
        JWTAuth::shouldReceive('invalidate')
            ->once()
            ->with($token)
            ->andReturn(true);
        
        // 2. Act - Thực hiện hành động
        $result = $this->authService->logout();
        
        // 3. Assert - Kiểm tra kết quả
        $this->assertTrue($result);
    }
    
    /**
     * Test đăng xuất khi không có token
     *
     * @return void
     */
    public function testLogoutWithNoToken()
    {
        // 1. Arrange - Chuẩn bị mock cho JWT
        JWTAuth::shouldReceive('getToken')
            ->once()
            ->andReturn(null);
        
        // 2. Act - Thực hiện hành động
        $result = $this->authService->logout();
        
        // 3. Assert - Kiểm tra kết quả
        $this->assertTrue($result);
    }
    
    /**
     * Test đăng xuất với token hết hạn
     *
     * @return void
     */
    public function testLogoutWithExpiredToken()
    {
        // 1. Arrange - Chuẩn bị mock cho JWT
        $token = 'expired_token';
        
        JWTAuth::shouldReceive('getToken')
            ->once()
            ->andReturn($token);
            
        JWTAuth::shouldReceive('invalidate')
            ->once()
            ->with($token)
            ->andThrow(new \Tymon\JWTAuth\Exceptions\TokenExpiredException());
        
        // 2. Act - Thực hiện hành động
        $result = $this->authService->logout();
        
        // 3. Assert - Kiểm tra kết quả
        $this->assertTrue($result);
    }
    
    /**
     * Test đăng xuất với token không hợp lệ
     *
     * @return void
     */
    public function testLogoutWithInvalidToken()
    {
        // 1. Arrange - Chuẩn bị mock cho JWT
        $token = 'invalid_token';
        
        JWTAuth::shouldReceive('getToken')
            ->once()
            ->andReturn($token);
            
        JWTAuth::shouldReceive('invalidate')
            ->once()
            ->with($token)
            ->andThrow(new \Tymon\JWTAuth\Exceptions\TokenInvalidException());
        
        // 2. Act - Thực hiện hành động
        $result = $this->authService->logout();
        
        // 3. Assert - Kiểm tra kết quả
        $this->assertTrue($result);
    }
    
    /**
     * Test đăng xuất với lỗi JWT
     *
     * @return void
     */
    public function testLogoutWithJwtException()
    {
        // 1. Arrange - Chuẩn bị mock cho JWT
        $token = 'problematic_token';
        $exception = new \Tymon\JWTAuth\Exceptions\JWTException('JWT error occurred');
        
        JWTAuth::shouldReceive('getToken')
            ->once()
            ->andReturn($token);
            
        JWTAuth::shouldReceive('invalidate')
            ->once()
            ->with($token)
            ->andThrow($exception);
        
        // 2 & 3. Act & Assert - Thực hiện và kiểm tra kết quả
        $this->expectException(\Tymon\JWTAuth\Exceptions\JWTException::class);
        $this->expectExceptionMessage('JWT error occurred');
        
        $this->authService->logout();
    }
    
    /**
     * Test lấy thông tin người dùng đã xác thực thành công
     *
     * @return void
     */
    public function testGetAuthenticatedUserSuccessfully()
    {
        // 1. Arrange - Chuẩn bị mock cho JWTAuth và user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => User::ROLE_STUDENT,
        ]);
        
        $jwtMock = Mockery::mock('Tymon\JWTAuth\JWTAuth');
        
        JWTAuth::shouldReceive('parseToken')
            ->once()
            ->andReturn($jwtMock);
        
        $jwtMock->shouldReceive('authenticate')
            ->once()
            ->andReturn($user);
        
        // 2. Act - Thực hiện hành động
        $result = $this->authService->getAuthenticatedUser();
        
        // 3. Assert - Kiểm tra kết quả
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
        $this->assertEquals('Test User', $result->name);
        $this->assertEquals('test@example.com', $result->email);
        $this->assertEquals(User::ROLE_STUDENT, $result->role);
    }
    
    /**
     * Test lấy thông tin với token không hợp lệ
     *
     * @return void
     */
    public function testGetAuthenticatedUserWithInvalidToken()
    {
        // 1. Arrange - Chuẩn bị mock cho JWTAuth
        $exception = new \Tymon\JWTAuth\Exceptions\TokenInvalidException('Token is Invalid');
        
        JWTAuth::shouldReceive('parseToken')
            ->once()
            ->andThrow($exception);
        
        // 2 & 3. Act & Assert - Thực hiện và kiểm tra kết quả
        $this->expectException(\Tymon\JWTAuth\Exceptions\TokenInvalidException::class);
        $this->expectExceptionMessage('Token is Invalid');
        
        $this->authService->getAuthenticatedUser();
    }
    
    /**
     * Test lấy thông tin khi không tìm thấy người dùng
     *
     * @return void
     */
    public function testGetAuthenticatedUserWithUserNotFound()
    {
        // 1. Arrange - Chuẩn bị mock cho JWTAuth
        $jwtMock = Mockery::mock('Tymon\JWTAuth\JWTAuth');
        
        JWTAuth::shouldReceive('parseToken')
            ->once()
            ->andReturn($jwtMock);
        
        $jwtMock->shouldReceive('authenticate')
            ->once()
            ->andReturn(null);
        
        // 2 & 3. Act & Assert - Thực hiện và kiểm tra kết quả
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->expectExceptionMessage('Không tìm thấy người dùng');
        
        $this->authService->getAuthenticatedUser();
    }
}
