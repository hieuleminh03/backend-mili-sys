<?php

namespace Tests\Unit;

use App\Models\FitnessTest;
use App\Models\FitnessTestThreshold;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FitnessTestThresholdModelTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Test the validation of correctly ordered thresholds for higher_is_better=true.
     *
     * @return void
     */
    public function test_validate_threshold_order_for_higher_is_better_true()
    {
        // Create a fitness test with higher values being better
        $fitnessTest = FitnessTest::factory()->create([
            'name' => 'Swimming Test',
            'unit' => 'meters',
            'higher_is_better' => true,
        ]);
        
        // Create thresholds with correct order (excellent > good > pass)
        $threshold = FitnessTestThreshold::factory()->create([
            'fitness_test_id' => $fitnessTest->id,
            'excellent_threshold' => 100,
            'good_threshold' => 75,
            'pass_threshold' => 50,
        ]);
        
        // Get the test with thresholds
        $fitnessTest->refresh();
        
        // Validation should pass
        $this->assertTrue($threshold->validateThresholdOrder());
        
        // Update with incorrect order (excellent < good)
        $threshold->excellent_threshold = 70; // Now less than good (75)
        $threshold->save();
        
        // Validation should fail
        $result = $threshold->validateThresholdOrder();
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertStringContainsString('Ngưỡng Giỏi phải cao hơn', $result[0]);
        
        // Fix excellent but break good < pass
        $threshold->excellent_threshold = 100;
        $threshold->good_threshold = 40; // Now less than pass (50)
        $threshold->save();
        
        // Validation should fail again
        $result = $threshold->validateThresholdOrder();
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertStringContainsString('Ngưỡng Khá phải cao hơn', $result[0]);
    }
    
    /**
     * Test the validation of correctly ordered thresholds for higher_is_better=false.
     *
     * @return void
     */
    public function test_validate_threshold_order_for_higher_is_better_false()
    {
        // Create a fitness test with lower values being better
        $fitnessTest = FitnessTest::factory()->create([
            'name' => 'Running 100m',
            'unit' => 'seconds',
            'higher_is_better' => false,
        ]);
        
        // Create thresholds with correct order (excellent < good < pass)
        $threshold = FitnessTestThreshold::factory()->create([
            'fitness_test_id' => $fitnessTest->id,
            'excellent_threshold' => 13.5,
            'good_threshold' => 14.0,
            'pass_threshold' => 14.5,
        ]);
        
        // Get the test with thresholds
        $fitnessTest->refresh();
        
        // Validation should pass
        $this->assertTrue($threshold->validateThresholdOrder());
        
        // Update with incorrect order (excellent > good)
        $threshold->excellent_threshold = 14.2; // Now more than good (14.0)
        $threshold->save();
        
        // Validation should fail
        $result = $threshold->validateThresholdOrder();
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertStringContainsString('Ngưỡng Giỏi phải thấp hơn', $result[0]);
        
        // Fix excellent but break good > pass
        $threshold->excellent_threshold = 13.5;
        $threshold->good_threshold = 15.0; // Now more than pass (14.5)
        $threshold->save();
        
        // Validation should fail again
        $result = $threshold->validateThresholdOrder();
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertStringContainsString('Ngưỡng Khá phải thấp hơn', $result[0]);
    }
    
    /**
     * Test the validation when the fitness test is missing.
     *
     * @return void
     */
    public function test_validate_threshold_order_with_missing_fitness_test()
    {
        // Create a threshold without a valid fitness test reference
        $threshold = new FitnessTestThreshold([
            'fitness_test_id' => 999, // Non-existent ID
            'excellent_threshold' => 100,
            'good_threshold' => 75,
            'pass_threshold' => 50,
        ]);
        
        // Validation should still pass because we can't validate without a test
        $this->assertTrue($threshold->validateThresholdOrder());
    }
}