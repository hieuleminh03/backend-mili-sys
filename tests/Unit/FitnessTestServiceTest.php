<?php

namespace Tests\Unit;

use App\Models\FitnessTest;
use App\Models\FitnessTestThreshold;
use App\Models\FitnessAssessmentSession;
use App\Models\StudentFitnessRecord;
use App\Models\User;
use App\Services\FitnessTestService;
use App\Http\Resources\FitnessTestResource;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Carbon\Carbon;

class FitnessTestServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected FitnessTestService $fitnessTestService;
    
    public function setUp(): void
    {
        parent::setUp();
        $this->fitnessTestService = new FitnessTestService();
    }
    
    /**
     * Test creating a fitness test with thresholds.
     *
     * @return void
     */
    public function test_create_fitness_test()
    {
        $data = [
            'name' => 'Running 400m',
            'unit' => 'seconds',
            'higher_is_better' => false,
            'excellent_threshold' => 60.0,
            'good_threshold' => 70.0,
            'pass_threshold' => 80.0,
        ];
        
        $fitnessTestResource = $this->fitnessTestService->createFitnessTest($data);
        
        // Assert the fitness test was created
        $this->assertInstanceOf(FitnessTestResource::class, $fitnessTestResource);
        $this->assertEquals('Running 400m', $fitnessTestResource->resource->name);
        $this->assertEquals('seconds', $fitnessTestResource->resource->unit);
        $this->assertFalse($fitnessTestResource->resource->higher_is_better);
        
        // Assert the thresholds were created
        $this->assertNotNull($fitnessTestResource->resource->thresholds);
        $this->assertEquals(60.0, $fitnessTestResource->resource->thresholds->excellent_threshold);
        $this->assertEquals(70.0, $fitnessTestResource->resource->thresholds->good_threshold);
        $this->assertEquals(80.0, $fitnessTestResource->resource->thresholds->pass_threshold);
    }
    
    /**
     * Test updating a fitness test with thresholds.
     *
     * @return void
     */
    public function test_update_fitness_test()
    {
        // Create a fitness test first
        $fitnessTest = FitnessTest::factory()->create([
            'name' => 'Original Test',
            'unit' => 'seconds',
            'higher_is_better' => false,
        ]);
        
        FitnessTestThreshold::factory()->create([
            'fitness_test_id' => $fitnessTest->id,
            'excellent_threshold' => 60.0,
            'good_threshold' => 70.0,
            'pass_threshold' => 80.0,
        ]);
        
        // Update data
        $updateData = [
            'name' => 'Updated Test',
            'unit' => 'minutes',
            'excellent_threshold' => 1.0,
            'good_threshold' => 1.5,
            'pass_threshold' => 2.0,
        ];
        
        $updatedTest = $this->fitnessTestService->updateFitnessTest($fitnessTest->id, $updateData);
        
        // Assert the fitness test was updated
        $this->assertInstanceOf(FitnessTestResource::class, $updatedTest);
        $this->assertEquals('Updated Test', $updatedTest->resource->name);
        $this->assertEquals('minutes', $updatedTest->resource->unit);
        $this->assertFalse($updatedTest->resource->higher_is_better); // Unchanged
        
        // Assert the thresholds were updated
        $this->assertEquals(1.0, $updatedTest->resource->thresholds->excellent_threshold);
        $this->assertEquals(1.5, $updatedTest->resource->thresholds->good_threshold);
        $this->assertEquals(2.0, $updatedTest->resource->thresholds->pass_threshold);
    }
    
    /**
     * Test updating a fitness test with invalid threshold order.
     *
     * @return void
     */
    public function test_update_fitness_test_with_invalid_threshold_order()
    {
        // Create a fitness test with higher_is_better = true
        $fitnessTest = FitnessTest::factory()->create([
            'name' => 'Push-ups',
            'unit' => 'count',
            'higher_is_better' => true,
        ]);
        
        FitnessTestThreshold::factory()->create([
            'fitness_test_id' => $fitnessTest->id,
            'excellent_threshold' => 30.0,
            'good_threshold' => 20.0,
            'pass_threshold' => 10.0,
        ]);
        
        // Update data with invalid order (excellent < good)
        $updateData = [
            'excellent_threshold' => 15.0, // Less than good
            'good_threshold' => 20.0,
            'pass_threshold' => 10.0,
        ];
        
        // Should throw an exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Lỗi ngưỡng đánh giá:');
        
        $this->fitnessTestService->updateFitnessTest($fitnessTest->id, $updateData);
    }
    
    /**
     * Test deleting a fitness test.
     *
     * @return void
     */
    public function test_delete_fitness_test()
    {
        // Create a fitness test
        $fitnessTest = FitnessTest::factory()->create();
        FitnessTestThreshold::factory()->create(['fitness_test_id' => $fitnessTest->id]);
        
        // Delete it
        $result = $this->fitnessTestService->deleteFitnessTest($fitnessTest->id);
        
        // Assert it was deleted
        $this->assertTrue($result);
        $this->assertSoftDeleted('fitness_tests', ['id' => $fitnessTest->id]);
        // Thresholds should be hard deleted or handled by cascade if set up
        $this->assertDatabaseMissing('fitness_test_thresholds', ['fitness_test_id' => $fitnessTest->id]);
    }
    
    /**
     * Test deleting a fitness test that has assessment records.
     *
     * @return void
     */
    public function test_cannot_delete_fitness_test_with_records()
    {
        // Create a fitness test
        $fitnessTest = FitnessTest::factory()->create();
        FitnessTestThreshold::factory()->create(['fitness_test_id' => $fitnessTest->id]);
        
        // Create a student and manager
        $student = User::factory()->create(['role' => 'student']);
        $manager = User::factory()->create(['role' => 'manager']);
        $session = FitnessAssessmentSession::factory()->create();
        
        // Create an assessment record
        StudentFitnessRecord::factory()->create([
            'user_id' => $student->id,
            'manager_id' => $manager->id,
            'fitness_test_id' => $fitnessTest->id,
            'assessment_session_id' => $session->id,
        ]);
        
        // Should throw an exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Không thể xóa bài kiểm tra này vì đã có kết quả đánh giá liên quan');
        
        $this->fitnessTestService->deleteFitnessTest($fitnessTest->id);
    }
    
    /**
     * Test recording a fitness assessment.
     *
     * @return void
     */
    public function test_record_fitness_assessment()
    {
        // Create a fitness test
        $fitnessTest = FitnessTest::factory()->create([
            'name' => 'Running Test',
            'unit' => 'seconds',
            'higher_is_better' => false,
        ]);
        
        FitnessTestThreshold::factory()->create([
            'fitness_test_id' => $fitnessTest->id,
            'excellent_threshold' => 13.5,
            'good_threshold' => 14.0,
            'pass_threshold' => 14.5,
        ]);
        
        // Create a student and manager
        $student = User::factory()->create(['role' => 'student']);
        $manager = User::factory()->create(['role' => 'manager']);
        
        // Create a session
        $session = FitnessAssessmentSession::factory()->create();
        
        // Record an assessment
        $data = [
            'user_id' => $student->id,
            'fitness_test_id' => $fitnessTest->id,
            'assessment_session_id' => $session->id,
            'performance' => 13.2, // Excellent performance
            'notes' => 'Good effort',
        ];
        
        $record = $this->fitnessTestService->recordFitnessAssessment($data, $manager);
        
        // Assert the record was created with correct rating
        $this->assertInstanceOf(StudentFitnessRecord::class, $record);
        $this->assertEquals($student->id, $record->user_id);
        $this->assertEquals($manager->id, $record->manager_id);
        $this->assertEquals($fitnessTest->id, $record->fitness_test_id);
        $this->assertEquals(13.2, $record->performance);
        $this->assertEquals('excellent', $record->rating);
    }
    
    /**
     * Test recording a fitness assessment for a student who already has a record.
     *
     * @return void
     */
    public function test_cannot_record_duplicate_assessment()
    {
        // Create a fitness test
        $fitnessTest = FitnessTest::factory()->create();
        FitnessTestThreshold::factory()->create(['fitness_test_id' => $fitnessTest->id]);
        
        // Create a student and manager
        $student = User::factory()->create(['role' => 'student']);
        $manager = User::factory()->create(['role' => 'manager']);
        $session = FitnessAssessmentSession::factory()->create();
        
        // Create an existing assessment
        StudentFitnessRecord::factory()->create([
            'user_id' => $student->id,
            'manager_id' => $manager->id,
            'fitness_test_id' => $fitnessTest->id,
            'assessment_session_id' => $session->id,
        ]);
        
        // Try to create another assessment
        $data = [
            'user_id' => $student->id,
            'fitness_test_id' => $fitnessTest->id,
            'assessment_session_id' => $session->id,
            'performance' => 100,
        ];
        
        // Should throw an exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Học viên này đã được đánh giá');
        
        $this->fitnessTestService->recordFitnessAssessment($data, $manager);
    }
    
    /**
     * Test batch recording of fitness assessments.
     *
     * @return void
     */
    public function test_batch_record_fitness_assessments()
    {
        // Create a fitness test
        $fitnessTest = FitnessTest::factory()->create([
            'name' => 'Running Test',
            'unit' => 'seconds',
            'higher_is_better' => false,
        ]);
        
        FitnessTestThreshold::factory()->create([
            'fitness_test_id' => $fitnessTest->id,
            'excellent_threshold' => 13.5,
            'good_threshold' => 14.0,
            'pass_threshold' => 14.5,
        ]);
        
        // Create students and manager
        $student1 = User::factory()->create(['role' => 'student']);
        $student2 = User::factory()->create(['role' => 'student']);
        $student3 = User::factory()->create(['role' => 'student']);
        $manager = User::factory()->create(['role' => 'manager']);
        
        // Create a session
        $session = FitnessAssessmentSession::factory()->create();
        
        // Batch record assessments
        $assessments = [
            [
                'user_id' => $student1->id,
                'performance' => 13.2, // Excellent
                'notes' => 'Very good',
            ],
            [
                'user_id' => $student2->id,
                'performance' => 13.7, // Good
            ],
            [
                'user_id' => $student3->id,
                'performance' => 14.2, // Pass
            ],
        ];
        
        $result = $this->fitnessTestService->batchRecordFitnessAssessments(
            $fitnessTest->id,
            $assessments,
            $session->id,
            $manager
        );
        
        // Assert all records were created
        $this->assertCount(3, $result['success']);
        $this->assertEmpty($result['failed']);
        $this->assertEmpty($result['already_exists']);
        
        // Assert database records
        $this->assertDatabaseHas('student_fitness_records', [
            'user_id' => $student1->id,
            'fitness_test_id' => $fitnessTest->id,
            'performance' => 13.2,
            'rating' => 'excellent',
        ]);
        
        $this->assertDatabaseHas('student_fitness_records', [
            'user_id' => $student2->id,
            'fitness_test_id' => $fitnessTest->id,
            'performance' => 13.7,
            'rating' => 'good',
        ]);
        
        $this->assertDatabaseHas('student_fitness_records', [
            'user_id' => $student3->id,
            'fitness_test_id' => $fitnessTest->id,
            'performance' => 14.2,
            'rating' => 'pass',
        ]);
    }
    
    /**
     * Test batch recording with duplicate assessment.
     *
     * @return void
     */
    public function test_batch_record_with_existing_record()
    {
        // Create a fitness test
        $fitnessTest = FitnessTest::factory()->create();
        FitnessTestThreshold::factory()->create(['fitness_test_id' => $fitnessTest->id]);
        
        // Create students and manager
        $student1 = User::factory()->create(['role' => 'student']);
        $student2 = User::factory()->create(['role' => 'student']);
        $manager = User::factory()->create(['role' => 'manager']);
        
        // Create a session
        $session = FitnessAssessmentSession::factory()->create();
        
        // Create an existing record
        StudentFitnessRecord::factory()->create([
            'user_id' => $student1->id,
            'manager_id' => $manager->id,
            'fitness_test_id' => $fitnessTest->id,
            'assessment_session_id' => $session->id,
        ]);
        
        // Try batch recording including the existing student
        $assessments = [
            [
                'user_id' => $student1->id, // Already has a record
                'performance' => 100,
            ],
            [
                'user_id' => $student2->id, // New record
                'performance' => 90,
            ],
        ];
        
        // Should throw an exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Một số học viên đã được đánh giá');
        
        $this->fitnessTestService->batchRecordFitnessAssessments(
            $fitnessTest->id,
            $assessments,
            $session->id,
            $manager
        );
    }
    
    /**
     * Test batch recording with invalid student ID.
     *
     * @return void
     */
    public function test_batch_record_with_invalid_student()
    {
        // Create a fitness test
        $fitnessTest = FitnessTest::factory()->create();
        FitnessTestThreshold::factory()->create(['fitness_test_id' => $fitnessTest->id]);
        
        // Create a student and manager
        $student = User::factory()->create(['role' => 'student']);
        $manager = User::factory()->create(['role' => 'manager']);
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Create a session
        $session = FitnessAssessmentSession::factory()->create();
        
        // Try batch recording with one valid student and one admin
        $assessments = [
            [
                'user_id' => $student->id, // Valid student
                'performance' => 100,
            ],
            [
                'user_id' => $admin->id, // Admin, not a student
                'performance' => 90,
            ],
            [
                'user_id' => 999, // Non-existent ID
                'performance' => 80,
            ],
        ];
        
        // Should throw an exception due to failures
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Có lỗi xảy ra trong quá trình đánh giá');
        
        $this->fitnessTestService->batchRecordFitnessAssessments(
            $fitnessTest->id,
            $assessments,
            $session->id,
            $manager
        );
    }
}