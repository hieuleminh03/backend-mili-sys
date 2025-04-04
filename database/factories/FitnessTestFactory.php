<?php

namespace Database\Factories;

use App\Models\FitnessTest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FitnessTest>
 */
class FitnessTestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FitnessTest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'unit' => $this->faker->randomElement(['seconds', 'meters', 'count', 'reps']),
            'higher_is_better' => $this->faker->boolean,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Configure the factory to create a test where higher scores are better.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function higherIsBetter()
    {
        return $this->state(function (array $attributes) {
            return [
                'higher_is_better' => true,
            ];
        });
    }

    /**
     * Configure the factory to create a test where lower scores are better.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function lowerIsBetter()
    {
        return $this->state(function (array $attributes) {
            return [
                'higher_is_better' => false,
            ];
        });
    }
}
