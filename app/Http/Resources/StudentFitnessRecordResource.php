<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentFitnessRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request  The incoming request.
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Eager load relationships if not already loaded
        $this->resource->loadMissing(['fitnessTest', 'assessmentSession']);

        return [
            'id' => $this->id,
            'fitness_test_id' => $this->fitness_test_id,
            'fitness_test_name' => $this->whenLoaded('fitnessTest', function () {
                return $this->fitnessTest->name; // Get name from related FitnessTest
            }),
            'assessment_session_id' => $this->assessment_session_id,
            'session_date' => $this->whenLoaded('assessmentSession', function () {
                return $this->assessmentSession->session_date; // Get date from related session
            }),
            'performance' => $this->performance,
            'rating' => $this->rating,
            'notes' => $this->notes,
            'recorded_at' => $this->created_at, // Assuming created_at is the recording time
            'updated_at' => $this->updated_at,
        ];
    }
}
