<?php

namespace Database\Factories;

use App\Models\FitnessTest;
use App\Models\FitnessTestThreshold;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FitnessTestThreshold>
 */
class FitnessTestThresholdFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FitnessTestThreshold::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fitnessTest = FitnessTest::factory()->create();
        $isHigherBetter = $fitnessTest->higher_is_better;

        if ($isHigherBetter) {
            // Higher is better: excellent > good > pass
            $pass = $this->faker->numberBetween(10, 30);
            $good = $this->faker->numberBetween($pass, $pass + 20);
            $excellent = $this->faker->numberBetween($good, $good + 20);
        } else {
            // Lower is better: excellent < good < pass
            $excellent = $this->faker->numberBetween(10, 30);
            $good = $this->faker->numberBetween($excellent, $excellent + 20);
            $pass = $this->faker->numberBetween($good, $good + 20);
        }

        return [
            'fitness_test_id' => $fitnessTest->id,
            'excellent_threshold' => $excellent,
            'good_threshold' => $good,
            'pass_threshold' => $pass,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Configure the factory to use an existing fitness test.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function forTest(FitnessTest $fitnessTest)
    {
        $isHigherBetter = $fitnessTest->higher_is_better;

        if ($isHigherBetter) {
            // Higher is better: excellent > good > pass
            $pass = $this->faker->numberBetween(10, 30);
            $good = $this->faker->numberBetween($pass, $pass + 20);
            $excellent = $this->faker->numberBetween($good, $good + 20);
        } else {
            // Lower is better: excellent < good < pass
            $excellent = $this->faker->numberBetween(10, 30);
            $good = $this->faker->numberBetween($excellent, $excellent + 20);
            $pass = $this->faker->numberBetween($good, $good + 20);
        }

        return $this->state(function (array $attributes) use ($fitnessTest, $excellent, $good, $pass) {
            return [
                'fitness_test_id' => $fitnessTest->id,
                'excellent_threshold' => $excellent,
                'good_threshold' => $good,
                'pass_threshold' => $pass,
            ];
        });
    }
}
