<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Case 1: Student detail view (studentDetail is loaded, this is the primary change)
        if ($this->isStudent() && $this->whenLoaded('studentDetail') && $this->studentDetail) {
            $studentData = (new StudentDetailResource($this->studentDetail))->toArray($request);

            // Add class role if available
            if ($this->whenLoaded('studentClass') && $this->studentClass) {
                $studentData['class_role'] = \App\Models\StudentClass::ROLES[$this->studentClass->role] ?? $this->studentClass->role;
            }
            return $studentData; // This now contains flattened student details + class_role
        }

        // Case 2: General user view (non-student, or student without studentDetail loaded)
        // This part handles other scenarios, like listing users, or manager views.
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'image' => $this->image,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        // If it's a student (but studentDetail wasn't loaded for the flattened view),
        // still include class_role if available (maintains original behavior for lists etc.)
        if ($this->isStudent() && $this->whenLoaded('studentClass') && $this->studentClass) {
            $data['class_role'] = \App\Models\StudentClass::ROLES[$this->studentClass->role] ?? $this->studentClass->role;
        }

        // Include manager details if available and the user is a manager
        if ($this->isManager() && $this->whenLoaded('managerDetail') && $this->managerDetail) {
            $data['manager_detail'] = [
                'id' => $this->managerDetail->id,
                'full_name' => $this->managerDetail->full_name,
                'rank' => $this->managerDetail->rank,
                'birth_year' => $this->managerDetail->birth_year,
                'hometown' => $this->managerDetail->hometown,
                'phone_number' => $this->managerDetail->phone_number,
                'is_party_member' => $this->managerDetail->is_party_member,
                'management_unit' => $this->managerDetail->management_unit,
                'father_name' => $this->managerDetail->father_name,
                'father_birth_year' => $this->managerDetail->father_birth_year,
                'mother_name' => $this->managerDetail->mother_name,
                'mother_birth_year' => $this->managerDetail->mother_birth_year,
                'father_hometown' => $this->managerDetail->father_hometown,
                'mother_hometown' => $this->managerDetail->mother_hometown,
                'permanent_address' => $this->managerDetail->permanent_address,
                'role_political' => $this->managerDetail->role_political,
                'created_at' => $this->managerDetail->created_at,
                'updated_at' => $this->managerDetail->updated_at,
            ];
        }

        return $data;
    }
}
