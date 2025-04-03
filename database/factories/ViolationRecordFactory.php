<?php

namespace Database\Factories;

use App\Models\ViolationRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

class ViolationRecordFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ViolationRecord::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'violation_name' => $this->faker->sentence(4),
            'violation_date' => $this->faker->dateTimeThisMonth(),
            'notes' => $this->faker->paragraph(),
            'is_editable' => $this->faker->boolean(),
            // Add other fields as necessary, referencing the ViolationRecord model's columns
        ];
    }
}