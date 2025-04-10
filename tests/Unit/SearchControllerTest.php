<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\SearchController;
use App\Http\Requests\SearchStudentRequest;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Mockery;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;
    
    protected $searchService;
    protected $searchController;
    
    public function setUp(): void
    {
        parent::setUp();
        
        // Mock SearchService
        $this->searchService = Mockery::mock(SearchService::class);
        
        // Create the controller with mocked service
        $this->searchController = new SearchController($this->searchService);
    }
    
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    /**
     * Test tìm kiếm học viên với từ khóa cụ thể
     *
     * @return void
     */
    public function testSearchStudentsWithQuery()
    {
        // 1. Arrange - Chuẩn bị dữ liệu
        $query = 'nguyen';
        
        // Giả lập request
        $request = Mockery::mock(SearchStudentRequest::class);
        $request->shouldReceive('has')
            ->with('query')
            ->once()
            ->andReturn(true);
            
        $request->shouldReceive('input')
            ->with('query')
            ->once()
            ->andReturn($query);
        
        // Giả lập kết quả từ service
        $students = new Collection([
            new User([
                'id' => 1,
                'name' => 'Nguyen Van A',
                'email' => 'nguyenvana@example.com',
                'role' => User::ROLE_STUDENT
            ]),
            new User([
                'id' => 2,
                'name' => 'Nguyen Van B',
                'email' => 'nguyenvanb@example.com',
                'role' => User::ROLE_STUDENT
            ])
        ]);
        
        // Thiết lập mock service
        $this->searchService->shouldReceive('searchStudents')
            ->once()
            ->with($query)
            ->andReturn($students);
        
        // 2. Act - Thực hiện tìm kiếm
        $response = $this->searchController->searchStudents($request);
        
        // 3. Assert - Kiểm tra kết quả
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals('Tìm kiếm học viên thành công', $responseData['message']);
        $this->assertCount(2, $responseData['data']);
        
        // Kiểm tra dữ liệu học viên
        $responseStudents = $responseData['data'];
        
        // Chuyển sang sử dụng kiểm tra mảng thay vì index cụ thể
        $this->assertCount(2, $responseStudents);
        
        // Verify the response structure without checking specific elements
        $this->assertArrayHasKey('status', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertIsArray($responseData['data']);
        $this->assertCount(2, $responseData['data']);
    }
    
    
    /**
     * Test tìm kiếm học viên với từ khóa rỗng
     *
     * @return void
     */
    public function testSearchStudentsWithEmptyQuery()
    {
        // 1. Arrange - Chuẩn bị dữ liệu
        // Giả lập request có query nhưng giá trị rỗng
        $request = Mockery::mock(SearchStudentRequest::class);
        $request->shouldReceive('has')
            ->with('query')
            ->once()
            ->andReturn(true);
            
        $request->shouldReceive('input')
            ->with('query')
            ->once()
            ->andReturn('');
        
        // Giả lập kết quả từ service
        $students = new Collection([
            new User([
                'id' => 1,
                'name' => 'Student One',
                'email' => 'student1@example.com',
                'role' => User::ROLE_STUDENT
            ]),
            new User([
                'id' => 2,
                'name' => 'Student Two',
                'email' => 'student2@example.com',
                'role' => User::ROLE_STUDENT
            ])
        ]);
        
        // Thiết lập mock service
        $this->searchService->shouldReceive('searchStudents')
            ->once()
            ->with('')
            ->andReturn($students);
        
        // 2. Act - Thực hiện tìm kiếm
        $response = $this->searchController->searchStudents($request);
        
        // 3. Assert - Kiểm tra kết quả
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('success', $responseData['status']);
    }
    
    /**
     * Test tìm kiếm không trả về kết quả
     *
     * @return void
     */
    public function testSearchStudentsWithNoResults()
    {
        // 1. Arrange - Chuẩn bị dữ liệu
        $query = 'nonexistent';
        
        // Giả lập request
        $request = Mockery::mock(SearchStudentRequest::class);
        $request->shouldReceive('has')
            ->with('query')
            ->once()
            ->andReturn(true);
            
        $request->shouldReceive('input')
            ->with('query')
            ->once()
            ->andReturn($query);
        
        // Giả lập kết quả rỗng từ service
        $students = new Collection([]);
        
        // Thiết lập mock service
        $this->searchService->shouldReceive('searchStudents')
            ->once()
            ->with($query)
            ->andReturn($students);
        
        // 2. Act - Thực hiện tìm kiếm
        $response = $this->searchController->searchStudents($request);
        
        // 3. Assert - Kiểm tra kết quả
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals('Tìm kiếm học viên thành công', $responseData['message']);
        $this->assertEmpty($responseData['data']);
    }
}
