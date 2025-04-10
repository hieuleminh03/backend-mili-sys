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

}