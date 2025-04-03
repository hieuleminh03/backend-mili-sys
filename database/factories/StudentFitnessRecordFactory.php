<?php

namespace Database\Factories;

use App\Models\FitnessTest;
use App\Models\FitnessAssessmentSession;
use App\Models\StudentFitnessRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentFitnessRecord>
 */
class StudentFitnessRecordFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StudentFitnessRecord::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Create a student if one doesn't exist yet
        $student = User::factory()->create(['role' => 'student']);
        
        // Create a manager if one doesn't exist yet
        $manager = User::factory()->create(['role' => 'manager']);
        
        // Create a fitness test with thresholds
        $fitnessTest = FitnessTest::factory()->create();
        $fitnessTest->thresholds()->create([
            'excellent_threshold' => 30,
            'good_threshold' => 20,
            'pass_threshold' => 10,
        ]);
        
        // Create a session
        $session = FitnessAssessmentSession::factory()->create();
        
        // Generate a random performance value
        $performance = $this->faker->numberBetween(5, 50);
        
        // Calculate rating based on the test's thresholds
        $rating = 'fail'; // Default rating
        if ($fitnessTest->higher_is_better) {
            if ($performance >= 30) $rating = 'excellent';
            elseif ($performance >= 20) $rating = 'good';
            elseif ($performance >= 10) $rating = 'pass';
        } else {
            if ($performance <= 10) $rating = 'excellent';
            elseif ($performance <= 20) $rating = 'good';
            elseif ($performance <= 30) $rating = 'pass';
        }
        
        return [
            'user_id' => $student->id,
            'manager_id' => $manager->id,
            'fitness_test_id' => $fitnessTest->id,
            'assessment_session_id' => $session->id,
            'performance' => $performance,
            'rating' => $rating,
            'notes' => $this->faker->sentence,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
    
    /**
     * Configure the factory to use specified student, manager, fitness test and session.
     *
     * @param User $student
     * @param User $manager
     * @param FitnessTest $fitnessTest
     * @param FitnessAssessmentSession $session
     * @param float $performance Optional performance value
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withDetails(User $student, User $manager, FitnessTest $fitnessTest, FitnessAssessmentSession $session, float $performance = null)
    {
        $performance = $performance ?? $this->faker->numberBetween(5, 50);
        
        // Calculate rating based on the test's thresholds
        $rating = 'fail'; // Default rating
        if ($fitnessTest->thresholds) {
            if ($fitnessTest->higher_is_better) {
                if ($performance >= $fitnessTest->thresholds->excellent_threshold) $rating = 'excellent';
                elseif ($performance >= $fitnessTest->thresholds->good_threshold) $rating = 'good';
                elseif ($performance >= $fitnessTest->thresholds->pass_threshold) $rating = 'pass';
            } else {
                if ($performance <= $fitnessTest->thresholds->excellent_threshold) $rating = 'excellent';
                elseif ($performance <= $fitnessTest->thresholds->good_threshold) $rating = 'good';
                elseif ($performance <= $fitnessTest->thresholds->pass_threshold) $rating = 'pass';
            }
        }
        
        return $this->state(function (array $attributes) use ($student, $manager, $fitnessTest, $session, $performance, $rating) {
            return [
                'user_id' => $student->id,
                'manager_id' => $manager->id,
                'fitness_test_id' => $fitnessTest->id,
                'assessment_session_id' => $session->id,
                'performance' => $performance,
                'rating' => $rating,
            ];
        });
    }
}