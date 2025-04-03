<?php

namespace Database\Factories;

use App\Models\FitnessAssessmentSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FitnessAssessmentSession>
 */
class FitnessAssessmentSessionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FitnessAssessmentSession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-4 weeks', '+4 weeks');
        $endDate = Carbon::parse($startDate)->addDays(6); // End date is 6 days after start date (one week)
        
        return [
            'name' => 'Session ' . $this->faker->word,
            'week_start_date' => $startDate,
            'week_end_date' => $endDate,
            'notes' => $this->faker->sentence,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
    
    /**
     * Configure the factory to create a session for the current week.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function currentWeek()
    {
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek = $now->copy()->endOfWeek();
        
        return $this->state(function (array $attributes) use ($startOfWeek, $endOfWeek) {
            return [
                'name' => 'Current Week Session',
                'week_start_date' => $startOfWeek,
                'week_end_date' => $endOfWeek,
            ];
        });
    }
    
    /**
     * Configure the factory to create a session for a specific week.
     *
     * @param Carbon $weekStart The start date of the week
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function forWeek(Carbon $weekStart)
    {
        $weekEnd = $weekStart->copy()->addDays(6);
        
        return $this->state(function (array $attributes) use ($weekStart, $weekEnd) {
            return [
                'name' => 'Session for week of ' . $weekStart->format('Y-m-d'),
                'week_start_date' => $weekStart,
                'week_end_date' => $weekEnd,
            ];
        });
    }
}