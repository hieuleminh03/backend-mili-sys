<?php

namespace Tests\Unit;

use App\Models\FitnessTest;
use App\Models\FitnessTestThreshold;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FitnessTestModelTest extends TestCase
{
    use RefreshDatabase;
    
    /**
     * Test the FitnessTest model's determineRating method with a higher_is_better=true test.
     *
     * @return void
     */
    public function test_determine_rating_with_higher_is_better_true()
    {
        // Create a fitness test where higher values are better (eg. meters in swimming)
        $fitnessTest = FitnessTest::factory()->create([
            'name' => 'Swimming Test',
            'unit' => 'meters',
            'higher_is_better' => true,
        ]);
        
        // Create thresholds
        $threshold = FitnessTestThreshold::factory()->create([
            'fitness_test_id' => $fitnessTest->id,
            'excellent_threshold' => 100, // >= 100 meters is excellent
            'good_threshold' => 75,       // >= 75 meters is good
            'pass_threshold' => 50,       // >= 50 meters is pass
        ]);
        
        // Get the test with thresholds
        $fitnessTest->refresh();
        
        // Test excellent rating
        $this->assertEquals('excellent', $fitnessTest->determineRating(100));
        $this->assertEquals('excellent', $fitnessTest->determineRating(120));
        
        // Test good rating
        $this->assertEquals('good', $fitnessTest->determineRating(75));
        $this->assertEquals('good', $fitnessTest->determineRating(99));
        
        // Test pass rating
        $this->assertEquals('pass', $fitnessTest->determineRating(50));
        $this->assertEquals('pass', $fitnessTest->determineRating(74));
        
        // Test fail rating
        $this->assertEquals('fail', $fitnessTest->determineRating(49));
        $this->assertEquals('fail', $fitnessTest->determineRating(0));
    }
    
    /**
     * Test the FitnessTest model's determineRating method with a higher_is_better=false test.
     *
     * @return void
     */
    public function test_determine_rating_with_higher_is_better_false()
    {
        // Create a fitness test where lower values are better (eg. seconds in running)
        $fitnessTest = FitnessTest::factory()->create([
            'name' => 'Running 100m',
            'unit' => 'seconds',
            'higher_is_better' => false,
        ]);
        
        // Create thresholds
        $threshold = FitnessTestThreshold::factory()->create([
            'fitness_test_id' => $fitnessTest->id,
            'excellent_threshold' => 13.5, // <= 13.5 seconds is excellent
            'good_threshold' => 14.0,      // <= 14.0 seconds is good
            'pass_threshold' => 14.5,      // <= 14.5 seconds is pass
        ]);
        
        // Get the test with thresholds
        $fitnessTest->refresh();
        
        // Test excellent rating
        $this->assertEquals('excellent', $fitnessTest->determineRating(13.5));
        $this->assertEquals('excellent', $fitnessTest->determineRating(13.0));
        
        // Test good rating
        $this->assertEquals('good', $fitnessTest->determineRating(14.0));
        $this->assertEquals('good', $fitnessTest->determineRating(13.6));
        
        // Test pass rating
        $this->assertEquals('pass', $fitnessTest->determineRating(14.5));
        $this->assertEquals('pass', $fitnessTest->determineRating(14.1));
        
        // Test fail rating
        $this->assertEquals('fail', $fitnessTest->determineRating(14.6));
        $this->assertEquals('fail', $fitnessTest->determineRating(15.0));
    }
    
    /**
     * Test the FitnessTest model's determineRating method with missing thresholds.
     *
     * @return void
     */
    public function test_determine_rating_with_missing_thresholds()
    {
        // Create a fitness test without thresholds
        $fitnessTest = FitnessTest::factory()->create([
            'name' => 'Test without thresholds',
            'unit' => 'count',
            'higher_is_better' => true,
        ]);
        
        // Should always return fail since thresholds are missing
        $this->assertEquals('fail', $fitnessTest->determineRating(100));
    }
}