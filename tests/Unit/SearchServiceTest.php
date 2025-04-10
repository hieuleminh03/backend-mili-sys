<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected SearchService $searchService;
    
    public function setUp(): void
    {
        parent::setUp();
        $this->searchService = new SearchService();
    }
    
    
    /**
     * Test tìm kiếm học viên theo tên
     *
     * @return void
     */
    public function testSearchStudentsByName()
    {
        // 1. Arrange - Tạo dữ liệu mẫu
        $timestamp = time();
        
        User::factory()->create([
            'name' => 'Nguyen Van A',
            'email' => "nguyenvana{$timestamp}@example.com",
            'role' => User::ROLE_STUDENT
        ]);
        
        User::factory()->create([
            'name' => 'Nguyen Van B',
            'email' => "nguyenvanb{$timestamp}@example.com",
            'role' => User::ROLE_STUDENT
        ]);
        
        User::factory()->create([
            'name' => 'Tran Van C',
            'email' => "tranvanc{$timestamp}@example.com",
            'role' => User::ROLE_STUDENT
        ]);
        
        // 2. Act - Thực hiện tìm kiếm với từ khóa 'nguyen'
        $result = $this->searchService->searchStudents('nguyen');
        
        // 3. Assert - Kiểm tra kết quả
        $this->assertCount(2, $result);
        
        // Kiểm tra các tên có chứa 'nguyen'
        foreach ($result as $student) {
            $this->assertStringContainsStringIgnoringCase('nguyen', $student->name);
        }
    }
    
    /**
     * Test tìm kiếm học viên theo email
     *
     * @return void
     */
    public function testSearchStudentsByEmail()
    {
        // 1. Arrange - Tạo dữ liệu mẫu
        $timestamp = time();
        
        User::factory()->create([
            'name' => 'Student One',
            'email' => "student1{$timestamp}@gmail.com",
            'role' => User::ROLE_STUDENT
        ]);
        
        User::factory()->create([
            'name' => 'Student Two',
            'email' => "student2{$timestamp}@gmail.com",
            'role' => User::ROLE_STUDENT
        ]);
        
        User::factory()->create([
            'name' => 'Student Three',
            'email' => "student3{$timestamp}@yahoo.com",
            'role' => User::ROLE_STUDENT
        ]);
        
        // 2. Act - Thực hiện tìm kiếm với từ khóa 'gmail'
        $result = $this->searchService->searchStudents('gmail');
        
        // 3. Assert - Kiểm tra kết quả
        $this->assertCount(2, $result);
        
        // Kiểm tra các email có chứa 'gmail'
        foreach ($result as $student) {
            $this->assertStringContainsStringIgnoringCase('gmail', $student->email);
        }
    }
    
    /**
     * Test tìm kiếm không phân biệt chữ hoa/thường
     *
     * @return void
     */
    public function testSearchStudentsCaseInsensitive()
    {
        // 1. Arrange - Tạo dữ liệu mẫu
        $timestamp = time();
        
        User::factory()->create([
            'name' => 'UPPER CASE NAME',
            'email' => "uppercase{$timestamp}@example.com",
            'role' => User::ROLE_STUDENT
        ]);
        
        User::factory()->create([
            'name' => 'lower case name',
            'email' => "lowercase{$timestamp}@example.com",
            'role' => User::ROLE_STUDENT
        ]);
        
        User::factory()->create([
            'name' => 'Mixed Case Name',
            'email' => "mixedcase{$timestamp}@example.com",
            'role' => User::ROLE_STUDENT
        ]);
        
        // 2. Act - Tìm kiếm cả 3 dạng
        $resultUpper = $this->searchService->searchStudents('UPPER');
        $resultLower = $this->searchService->searchStudents('lower');
        $resultMixed = $this->searchService->searchStudents('Mixed');
        
        // 3. Assert - Kiểm tra kết quả
        $this->assertCount(1, $resultUpper);
        $this->assertCount(1, $resultLower);
        $this->assertCount(1, $resultMixed);
        
        // Kiểm tra tìm kiếm không phân biệt chữ hoa/thường
        $resultAll = $this->searchService->searchStudents('case');
        $this->assertCount(3, $resultAll);
    }
    
    /**
     * Test tìm kiếm không có kết quả
     *
     * @return void
     */
    public function testSearchStudentsNoResults()
    {
        // 1. Arrange - Tạo dữ liệu mẫu
        $timestamp = time();
        
        User::factory()->create([
            'name' => 'Student One',
            'email' => "student1_noresult_{$timestamp}@example.com",
            'role' => User::ROLE_STUDENT
        ]);
        
        User::factory()->create([
            'name' => 'Student Two',
            'email' => "student2_noresult_{$timestamp}@example.com",
            'role' => User::ROLE_STUDENT
        ]);
        
        // 2. Act - Thực hiện tìm kiếm với từ khóa không tồn tại
        $result = $this->searchService->searchStudents('nonexistent');
        
        // 3. Assert - Kiểm tra kết quả
        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
    }
    
    /**
     * Test tìm kiếm với từ khóa rỗng
     *
     * @return void
     */
    public function testSearchStudentsWithEmptyQuery()
    {
        // 1. Arrange - Tạo dữ liệu mẫu
        User::factory()->count(3)->create([
            'role' => User::ROLE_STUDENT
        ]);
        
        // 2. Act - Thực hiện tìm kiếm với từ khóa rỗng
        $result = $this->searchService->searchStudents('');
        
        // 3. Assert - Kiểm tra kết quả
        $this->assertCount(3, $result);
    }
    
    /**
     * Test tìm kiếm khi database không có học viên nào
     *
     * @return void
     */
    public function testSearchStudentsWithEmptyDatabase()
    {
        // 1. Arrange - Không tạo bất kỳ học viên nào
        
        // 2. Act - Thực hiện tìm kiếm
        $result = $this->searchService->searchStudents('query');
        
        // 3. Assert - Kiểm tra kết quả
        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
    }
}
