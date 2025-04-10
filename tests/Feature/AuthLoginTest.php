<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test đăng nhập thành công
     *
     * @return void
     */
    public function testLoginSuccess()
    {
        // 1. Arrange - Tạo một người dùng cho việc kiểm thử
        $email = 'test@example.com';
        $password = 'password123';
        
        $user = User::factory()->create([
            'email' => $email,
            'password' => Hash::make($password),
            'name' => 'Test User',
            'role' => 'student',
        ]);

        // 2. Act - Gửi request đăng nhập
        $response = $this->postJson('/api/login', [
            'email' => $email,
            'password' => $password,
        ]);

        // 3. Assert - Kiểm tra kết quả
        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'role',
                    ],
                ],
            ])
            ->assertJson([
                'status' => 'success',
                'message' => 'Đăng nhập thành công',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                    ],
                ],
            ]);

        // Kiểm tra token có tồn tại
        $this->assertNotEmpty($response->json('data.token'));
    }

    /**
     * Test đăng nhập với email không tồn tại
     *
     * @return void
     */
    public function testLoginWithNonExistentEmail()
    {
        // 1. Arrange - Chuẩn bị dữ liệu đăng nhập không hợp lệ
        $loginData = [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ];

        // 2. Act - Gửi request đăng nhập
        $response = $this->postJson('/api/login', $loginData);

        // 3. Assert - Kiểm tra kết quả
        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Thông tin đăng nhập không hợp lệ',
            ]);
    }

    /**
     * Test đăng nhập với mật khẩu không đúng
     *
     * @return void
     */
    public function testLoginWithIncorrectPassword()
    {
        // 1. Arrange - Tạo người dùng với mật khẩu đúng
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correctpassword'),
        ]);

        // 2. Act - Gửi request đăng nhập với mật khẩu sai
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        // 3. Assert - Kiểm tra kết quả
        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Thông tin đăng nhập không hợp lệ',
            ]);
    }

    /**
     * Test đăng nhập với dữ liệu không hợp lệ (thiếu trường bắt buộc)
     *
     * @return void
     */
    public function testLoginWithMissingData()
    {
        // 1. Act - Gửi request đăng nhập thiếu mật khẩu
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            // thiếu password
        ]);

        // 2. Assert - Kiểm tra kết quả
        $response->assertStatus(422) // 422 Unprocessable Entity
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test đăng nhập với email không đúng định dạng
     *
     * @return void
     */
    public function testLoginWithInvalidEmailFormat()
    {
        // 1. Act - Gửi request đăng nhập với email không hợp lệ
        $response = $this->postJson('/api/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        // 2. Assert - Kiểm tra kết quả
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}