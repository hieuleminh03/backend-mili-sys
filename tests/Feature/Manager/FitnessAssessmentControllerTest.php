<?php

namespace Tests\Feature\Manager;

use App\Models\FitnessTest;
use App\Models\FitnessTestThreshold;
use App\Models\FitnessAssessmentSession;
use App\Models\StudentFitnessRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;

class FitnessAssessmentControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    protected User $admin;
    protected User $manager;
    protected User $student;
    protected string $managerToken;
    protected FitnessTest $fitnessTest;
    protected FitnessAssessmentSession $session;
    
    public function setUp(): void
    {
        parent::setUp();
        
        // Create users with different roles
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->manager = User::factory()->create(['role' => 'manager']);
        $this->student = User::factory()->create(['role' => 'student']);
        
        // Generate token for manager
        $this->managerToken = auth()->login($this->manager);
        
        // Create a fitness test with thresholds
        $this->fitnessTest = FitnessTest::factory()->create([
            'name' => 'Running 100m',
            'unit' => 'seconds',
            'higher_is_better' => false,
        ]);
        
        FitnessTestThreshold::factory()->create([
            'fitness_test_id' => $this->fitnessTest->id,
            'excellent_threshold' => 13.5,
            'good_threshold' => 14.0,
            'pass_threshold' => 14.5,
        ]);
        
        // Create a session for the current week
        $now = Carbon::now();
        $this->session = FitnessAssessmentSession::create([
            'name' => 'Current Week Session',
            'week_start_date' => $now->copy()->startOfWeek(),
            'week_end_date' => $now->copy()->endOfWeek(),
        ]);
    }
    
    /**
     * Test getting all fitness tests as a manager.
     *
     * @return void
     */
    public function test_manager_can_get_all_fitness_tests()
    {
        // Make the request
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->getJson('/api/manager/fitness/tests');
            
        // Assert response
        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [ // Check for the pagination structure provided by FitnessTestCollection
                    'current_page',
                    'data' => [
                        '*' => [ // Check the structure of each fitness test item
                            'id',
                            'name',
                            'unit',
                            'higher_is_better',
                            'thresholds', // Ensure thresholds key exists
                        ],
                    ],
                    'first_page_url',
                    'from',
                    'last_page',
                    'last_page_url',
                    'links',
                    'next_page_url',
                    'path',
                    'per_page',
                    'prev_page_url',
                    'to',
                    'total',
                ],
            ]);
    }
    
    /**
     * Test getting all assessment sessions.
     *
     * @return void
     */
    public function test_manager_can_get_all_sessions()
    {
        // Create another session for last week
        $now = Carbon::now();
        FitnessAssessmentSession::create([
            'name' => 'Last Week Session',
            'week_start_date' => $now->copy()->subWeek()->startOfWeek(),
            'week_end_date' => $now->copy()->subWeek()->endOfWeek(),
        ]);
        
        // Make the request
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->getJson('/api/manager/fitness/sessions');
            
        // Assert response
        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'week_start_date',
                        'week_end_date',
                    ],
                ],
            ])
            ->assertJsonCount(2, 'data');
            
        // Test with current_week_only filter
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->getJson('/api/manager/fitness/sessions?current_week_only=true');
            
        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }
    
    /**
     * Test getting or creating the current week session.
     *
     * @return void
     */
    public function test_manager_can_get_current_week_session()
    {
        // Make the request
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->getJson('/api/manager/fitness/current-session');
            
        // Assert response
        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'name',
                    'week_start_date',
                    'week_end_date',
                ],
            ])
            ->assertJsonPath('data.id', $this->session->id);
    }
    
    /**
     * Test recording a fitness assessment.
     *
     * @return void
     */
    public function test_manager_can_record_assessment()
    {
        // Assessment data
        $data = [
            'user_id' => $this->student->id,
            'fitness_test_id' => $this->fitnessTest->id,
            'assessment_session_id' => $this->session->id,
            'performance' => 13.2, // Excellent
            'notes' => 'Good performance',
        ];
        
        // Make the request
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->postJson('/api/manager/fitness/assessments', $data);
            
        // Assert response
        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'user_id',
                    'manager_id',
                    'fitness_test_id',
                    'assessment_session_id',
                    'performance',
                    'rating',
                    'notes',
                ],
            ])
            ->assertJsonPath('data.rating', 'excellent')
            ->assertJsonPath('data.manager_id', $this->manager->id);
            
        // Assert database
        $this->assertDatabaseHas('student_fitness_records', [
            'user_id' => $this->student->id,
            'fitness_test_id' => $this->fitnessTest->id,
            'performance' => 13.2,
            'rating' => 'excellent',
        ]);
    }
    
    /**
     * Test cannot record duplicate assessment.
     *
     * @return void
     */
    public function test_cannot_record_duplicate_assessment()
    {
        // Create an existing assessment
        StudentFitnessRecord::create([
            'user_id' => $this->student->id,
            'manager_id' => $this->manager->id,
            'fitness_test_id' => $this->fitnessTest->id,
            'assessment_session_id' => $this->session->id,
            'performance' => 13.2,
            'rating' => 'excellent',
        ]);
        
        // Try to create another assessment
        $data = [
            'user_id' => $this->student->id,
            'fitness_test_id' => $this->fitnessTest->id,
            'assessment_session_id' => $this->session->id,
            'performance' => 14.0,
        ];
        
        // Make the request
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->postJson('/api/manager/fitness/assessments', $data);
        // Assert response contains error about duplicate assessment
        $response
            ->assertJsonStructure([
                'status',
                'message',
            ])
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', function ($message) {
                return str_contains($message, 'đã được đánh giá');
            });
    }
    
    /**
     * Test recording assessment in batch.
     *
     * @return void
     */
    public function test_manager_can_record_batch_assessment()
    {
        // Create more students
        $student2 = User::factory()->create(['role' => 'student']);
        $student3 = User::factory()->create(['role' => 'student']);
        
        // Batch data
        $data = [
            'fitness_test_id' => $this->fitnessTest->id,
            'assessment_session_id' => $this->session->id,
            'assessments' => [
                [
                    'user_id' => $this->student->id,
                    'performance' => 13.2, // Excellent
                    'notes' => 'Good job',
                ],
                [
                    'user_id' => $student2->id,
                    'performance' => 13.8, // Good
                ],
                [
                    'user_id' => $student3->id,
                    'performance' => 14.3, // Pass
                ],
            ],
        ];
        
        // Make the request
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->postJson('/api/manager/fitness/assessments/batch', $data);
            
        // Assert response
        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'success',
                    'failed',
                    'already_exists',
                    'message',
                ],
            ])
            ->assertJsonCount(3, 'data.success');
            
        // Assert database
        $this->assertDatabaseCount('student_fitness_records', 3);
        
        $this->assertDatabaseHas('student_fitness_records', [
            'user_id' => $this->student->id,
            'fitness_test_id' => $this->fitnessTest->id,
            'rating' => 'excellent',
        ]);
        
        $this->assertDatabaseHas('student_fitness_records', [
            'user_id' => $student2->id,
            'fitness_test_id' => $this->fitnessTest->id,
            'rating' => 'good',
        ]);
        
        $this->assertDatabaseHas('student_fitness_records', [
            'user_id' => $student3->id,
            'fitness_test_id' => $this->fitnessTest->id,
            'rating' => 'pass',
        ]);
    }
    
    /**
     * Test batch assessment with duplicate records fails.
     *
     * @return void
     */
    public function test_batch_assessment_with_duplicate_fails()
    {
        // Create an existing assessment
        StudentFitnessRecord::create([
            'user_id' => $this->student->id,
            'manager_id' => $this->manager->id,
            'fitness_test_id' => $this->fitnessTest->id,
            'assessment_session_id' => $this->session->id,
            'performance' => 13.2,
            'rating' => 'excellent',
        ]);
        
        // Create another student
        $student2 = User::factory()->create(['role' => 'student']);
        
        // Try batch with one existing and one new
        $data = [
            'fitness_test_id' => $this->fitnessTest->id,
            'assessment_session_id' => $this->session->id,
            'assessments' => [
                [
                    'user_id' => $this->student->id, // Already has record
                    'performance' => 13.5,
                ],
                [
                    'user_id' => $student2->id, // New
                    'performance' => 14.0,
                ],
            ],
        ];
        
        // Make the request
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->postJson('/api/manager/fitness/assessments/batch', $data);
            
        // Assert response contains error about duplicate assessment
        $response
            ->assertJsonPath('status', 'error')
            ->assertJsonStructure([
                'status',
                'message',
            ])
            ->assertJsonPath('message', function ($message) {
                return str_contains($message, 'đã được đánh giá');
            });
            
        // Assert no new records were created
        $this->assertDatabaseCount('student_fitness_records', 1);
    }
    
    /**
     * Test getting session assessments.
     *
     * @return void
     */
    public function test_manager_can_get_session_assessments()
    {
        // Create records for multiple students
        $student2 = User::factory()->create(['role' => 'student']);
        $student3 = User::factory()->create(['role' => 'student']);
        
        // Create another fitness test
        $fitnessTest2 = FitnessTest::factory()->create(['name' => 'Push-ups']);
        FitnessTestThreshold::factory()->create(['fitness_test_id' => $fitnessTest2->id]);
        
        // Create records for test 1
        StudentFitnessRecord::create([
            'user_id' => $this->student->id,
            'manager_id' => $this->manager->id,
            'fitness_test_id' => $this->fitnessTest->id,
            'assessment_session_id' => $this->session->id,
            'performance' => 13.2,
            'rating' => 'excellent',
        ]);
        
        StudentFitnessRecord::create([
            'user_id' => $student2->id,
            'manager_id' => $this->manager->id,
            'fitness_test_id' => $this->fitnessTest->id,
            'assessment_session_id' => $this->session->id,
            'performance' => 13.8,
            'rating' => 'good',
        ]);
        
        // Create record for test 2
        StudentFitnessRecord::create([
            'user_id' => $student3->id,
            'manager_id' => $this->manager->id,
            'fitness_test_id' => $fitnessTest2->id,
            'assessment_session_id' => $this->session->id,
            'performance' => 25,
            'rating' => 'excellent',
        ]);
        
        // Get all records for session
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->getJson('/api/manager/fitness/sessions/' . $this->session->id . '/assessments');
            
        $response->assertOk()
            ->assertJsonCount(3, 'data');
            
        // Filter by test
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->managerToken)
            ->getJson('/api/manager/fitness/sessions/' . $this->session->id . '/assessments?test_id=' . $this->fitnessTest->id);
            
        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }
    
    /**
     * Test non-manager cannot access assessment endpoints.
     *
     * @return void
     */
    public function test_non_manager_cannot_access_assessment_endpoints()
    {
        // Get student token
        $studentToken = auth()->login($this->student);
        
        // Try to access tests list
        $response = $this->withHeader('Authorization', 'Bearer ' . $studentToken)
            ->getJson('/api/manager/fitness/tests');
            
        $response->assertStatus(403);
        
        // Try to record assessment
        $response = $this->withHeader('Authorization', 'Bearer ' . $studentToken)
            ->postJson('/api/manager/fitness/assessments', [
                'user_id' => $this->student->id,
                'fitness_test_id' => $this->fitnessTest->id,
                'performance' => 13.5,
            ]);
            
        $response->assertStatus(403);
    }
}