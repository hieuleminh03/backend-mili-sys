<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\StudentClass; // Added for ROLES

class StudentProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'image' => $this->image,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'student_detail' => null, // Initialize
            'class_role' => null, // Initialize
        ];

        if ($this->whenLoaded('studentDetail') && $this->studentDetail) {
            $data['student_detail'] = (new StudentDetailResource($this->studentDetail))->toArray($request);
        }

        if ($this->whenLoaded('studentClass') && $this->studentClass) {
            $data['class_role'] = StudentClass::ROLES[$this->studentClass->role] ?? $this->studentClass->role;
        }
        
        return $data;
    }
}