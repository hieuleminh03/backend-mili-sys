<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            
            // Personal information
            'date_of_birth' => $this->date_of_birth,
            'rank' => $this->rank,
            'place_of_origin' => $this->place_of_origin,
            'working_unit' => $this->working_unit,
            'year_of_study' => $this->year_of_study,
            'political_status' => $this->political_status,
            'phone_number' => $this->phone_number,
            'permanent_residence' => $this->permanent_residence,
            
            // Father information
            'father' => [
                'name' => $this->father_name,
                'birth_year' => $this->father_birth_year,
                'phone_number' => $this->father_phone_number,
                'place_of_origin' => $this->father_place_of_origin,
                'occupation' => $this->father_occupation,
            ],
            // Mother information
            'mother' => [
                'name' => $this->mother_name,
                'birth_year' => $this->mother_birth_year,
                'phone_number' => $this->mother_phone_number,
                'place_of_origin' => $this->mother_place_of_origin,
                'occupation' => $this->mother_occupation,
            ],
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
