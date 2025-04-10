<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Mockery;

class RegisterServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuthService $authService;

    public function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
    }

    /**
     * Test đăng ký người dùng thành công với quyền admin
     *
     * @return void
     */
    public function testRegisterSuccessfully()
    {
        // 1. Arrange - Chuẩn bị dữ liệu
        $adminUser = User::factory()->create([
            'role' => User::ROLE_ADMIN
        ]);
        
        $userData = [
            'name' => 'New Student',
            'email' => 'newstudent@example.com',
            'password' => 'password123',
            'role' => User::ROLE_STUDENT
        ];

        // 2. Act - Thực hiện hành động
        $result = $this->authService->register($userData, $adminUser);

        // 3. Assert - Kiểm tra kết quả
        // Kiểm tra cấu trúc kết quả
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('role', $result);
        
        // Kiểm tra các giá trị trong kết quả
        $this->assertEquals('New Student', $result['name']);
        $this->assertEquals('newstudent@example.com', $result['email']);
        $this->assertEquals(User::ROLE_STUDENT, $result['role']);
        
        // Kiểm tra người dùng đã được tạo trong database
        $this->assertDatabaseHas('users', [
            'name' => 'New Student',
            'email' => 'newstudent@example.com',
            'role' => User::ROLE_STUDENT
        ]);
        
        // Kiểm tra mật khẩu được mã hóa
        $createdUser = User::where('email', 'newstudent@example.com')->first();
        $this->assertTrue(Hash::check('password123', $createdUser->password));
    }
    
    /**
     * Test đăng ký người dùng khi không phải admin
     *
     * @return void
     */
    public function testRegisterWithNonAdminUser()
    {
        // 1. Arrange - Chuẩn bị dữ liệu
        $nonAdminUser = User::factory()->create([
            'role' => User::ROLE_STUDENT
        ]);
        
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123'
        ];

        // 2 & 3. Act & Assert - Thực hiện và kiểm tra kết quả
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Chỉ người dùng có quyền admin mới được phép đăng ký tài khoản mới');
        
        $this->authService->register($userData, $nonAdminUser);
    }
    
    /**
     * Test đăng ký người dùng khi không cung cấp người dùng hiện tại
     *
     * @return void
     */
    public function testRegisterWithNoCurrentUser()
    {
        // 1. Arrange - Chuẩn bị dữ liệu
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123'
        ];

        // 2 & 3. Act & Assert - Thực hiện và kiểm tra kết quả
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Chỉ người dùng có quyền admin mới được phép đăng ký tài khoản mới');
        
        $this->authService->register($userData);
    }
    
    /**
     * Test đăng ký người dùng với dữ liệu không hợp lệ
     *
     * @return void
     */
    public function testRegisterWithInvalidData()
    {
        // 1. Arrange - Chuẩn bị dữ liệu
        $adminUser = User::factory()->create([
            'role' => User::ROLE_ADMIN
        ]);
        
        // Thiếu trường bắt buộc 'name'
        $userData = [
            'email' => 'newuser@example.com',
            'password' => 'password123'
        ];

        // 2 & 3. Act & Assert - Thực hiện và kiểm tra kết quả
        $this->expectException(ValidationException::class);
        
        $this->authService->register($userData, $adminUser);
    }
    
    /**
     * Test đăng ký người dùng với email đã tồn tại
     *
     * @return void
     */
    public function testRegisterWithExistingEmail()
    {
        // 1. Arrange - Chuẩn bị dữ liệu
        $adminUser = User::factory()->create([
            'role' => User::ROLE_ADMIN
        ]);
        
        // Tạo một user với email cụ thể
        User::factory()->create([
            'email' => 'existing@example.com'
        ]);
        
        // Thử đăng ký với email đã tồn tại
        $userData = [
            'name' => 'New User',
            'email' => 'existing@example.com',
            'password' => 'password123'
        ];

        // 2 & 3. Act & Assert - Thực hiện và kiểm tra kết quả
        $this->expectException(ValidationException::class);
        
        $this->authService->register($userData, $adminUser);
    }
    
    /**
     * Test đăng ký người dùng với vai trò không hợp lệ
     *
     * @return void
     */
    public function testRegisterWithInvalidRole()
    {
        // 1. Arrange - Chuẩn bị dữ liệu
        $adminUser = User::factory()->create([
            'role' => User::ROLE_ADMIN
        ]);
        
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'role' => 'invalid_role' // Vai trò không hợp lệ
        ];

        // 2 & 3. Act & Assert - Thực hiện và kiểm tra kết quả
        $this->expectException(ValidationException::class);
        
        $this->authService->register($userData, $adminUser);
    }
}
