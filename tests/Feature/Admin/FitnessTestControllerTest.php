<?php

namespace Tests\Feature\Admin;

use App\Models\FitnessTest;
use App\Models\FitnessTestThreshold;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class FitnessTestControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    protected User $admin;
    protected User $manager;
    protected User $student;
    protected string $token;
    
    public function setUp(): void
    {
        parent::setUp();
        
        // Create users with different roles
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->manager = User::factory()->create(['role' => 'manager']);
        $this->student = User::factory()->create(['role' => 'student']);
        
        // Generate token for admin
        $this->token = auth()->login($this->admin);
    }
    
    /**
     * Test getting all fitness tests.
     *
     * @return void
     */
    public function test_admin_can_get_all_fitness_tests()
    {
        // Create some fitness tests
        $fitnessTests = FitnessTest::factory()->count(3)->create();
        
        foreach ($fitnessTests as $test) {
            FitnessTestThreshold::factory()->create(['fitness_test_id' => $test->id]);
        }
        
        // Make the request
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/fitness-tests');
            
        // Assert response
        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'unit',
                            'higher_is_better',
                            'thresholds' // Just check that thresholds exist, not their structure
                        ],
                    ],
                    'total',
                ],
            ]);
    }
    
    /**
     * Test getting a specific fitness test.
     *
     * @return void
     */
    public function test_admin_can_get_specific_fitness_test()
    {
        // Create a fitness test with thresholds
        $fitnessTest = FitnessTest::factory()->create(['name' => 'Test 123']);
        FitnessTestThreshold::factory()->create(['fitness_test_id' => $fitnessTest->id]);
        
        // Make the request
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/fitness-tests/' . $fitnessTest->id);
            
        // Assert response
        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'name',
                    'unit',
                    'higher_is_better',
                    'thresholds',
                ],
            ])
            ->assertJsonPath('data.name', 'Test 123');
    }
    
    /**
     * Test creating a new fitness test.
     *
     * @return void
     */
    public function test_admin_can_create_fitness_test()
    {
        // Prepare test data
        $data = [
            'name' => 'New Fitness Test',
            'unit' => 'reps',
            'higher_is_better' => true,
            'excellent_threshold' => 30,
            'good_threshold' => 20,
            'pass_threshold' => 10,
        ];
        
        // Make the request
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/fitness-tests', $data);
            
        // Assert response
        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'name',
                    'unit',
                    'higher_is_better',
                    'thresholds',
                ],
            ])
            ->assertJsonPath('data.name', 'New Fitness Test');
            
        // Assert database
        $this->assertDatabaseHas('fitness_tests', [
            'name' => 'New Fitness Test',
            'unit' => 'reps',
            'higher_is_better' => true,
        ]);
    }
    
    /**
     * Test creating a fitness test with invalid data.
     *
     * @return void
     */
    public function test_cannot_create_fitness_test_with_invalid_data()
    {
        // Missing required fields
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/fitness-tests', [
                'name' => 'Incomplete Test',
                // Missing unit and thresholds
            ]);
            
        $response->assertStatus(422);
        
        // Invalid threshold order for higher_is_better = true
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/fitness-tests', [
                'name' => 'Invalid Test',
                'unit' => 'count',
                'higher_is_better' => true,
                'excellent_threshold' => 10, // Lower than good
                'good_threshold' => 20,
                'pass_threshold' => 30,
            ]);
            
        $response->assertStatus(422);
    }
    
    /**
     * Test updating a fitness test.
     *
     * @return void
     */
    public function test_admin_can_update_fitness_test()
    {
        // Create a fitness test with thresholds
        $fitnessTest = FitnessTest::factory()->create([
            'name' => 'Original Test',
            'unit' => 'seconds',
            'higher_is_better' => false,
        ]);
        
        FitnessTestThreshold::factory()->create([
            'fitness_test_id' => $fitnessTest->id,
            'excellent_threshold' => 10,
            'good_threshold' => 15,
            'pass_threshold' => 20,
        ]);
        
        // Update data
        $data = [
            'name' => 'Updated Test',
            'unit' => 'minutes',
            'excellent_threshold' => 1,
            'good_threshold' => 2,
            'pass_threshold' => 3,
        ];
        
        // Make the request
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/fitness-tests/' . $fitnessTest->id, $data);
            
        // Assert response
        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.name', 'Updated Test')
            ->assertJsonPath('data.unit', 'minutes');
        $this->assertNotNull($response->json('data.thresholds')); // Use $this->assertNotNull
            
        // Assert database
        $this->assertDatabaseHas('fitness_tests', [
            'id' => $fitnessTest->id,
            'name' => 'Updated Test',
            'unit' => 'minutes',
        ]);
        
        // Check the thresholds in the database
        $this->assertDatabaseHas('fitness_test_thresholds', [
            'fitness_test_id' => $fitnessTest->id,
            'excellent_threshold' => 1,
            'good_threshold' => 2,
            'pass_threshold' => 3,
        ]);
    }
    
    /**
     * Test deleting a fitness test.
     *
     * @return void
     */
    public function test_admin_can_delete_fitness_test()
    {
        // Create a fitness test with thresholds
        $fitnessTest = FitnessTest::factory()->create();
        FitnessTestThreshold::factory()->create(['fitness_test_id' => $fitnessTest->id]);
        
        // Make the request
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/fitness-tests/' . $fitnessTest->id);
            
        // Assert response
        $response->assertOk();
            
        // Assert database
        $this->assertSoftDeleted('fitness_tests', ['id' => $fitnessTest->id]);
    }
    
    /**
     * Test non-admin cannot access fitness test endpoints.
     *
     * @return void
     */
    public function test_non_admin_cannot_access_fitness_test_endpoints()
    {
        // Generate manager token
        $managerToken = auth()->login($this->manager);
        
        // Try to access getAll endpoint
        $response = $this->withHeader('Authorization', 'Bearer ' . $managerToken)
            ->getJson('/api/fitness-tests');
            
        $response->assertStatus(403);
        
        // Try to create a fitness test
        $response = $this->withHeader('Authorization', 'Bearer ' . $managerToken)
            ->postJson('/api/fitness-tests', [
                'name' => 'Test',
                'unit' => 'count',
                'higher_is_better' => true,
                'excellent_threshold' => 30,
                'good_threshold' => 20,
                'pass_threshold' => 10,
            ]);
            
        $response->assertStatus(403);
    }
}