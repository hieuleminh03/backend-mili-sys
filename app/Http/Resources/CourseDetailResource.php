<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        // Get the current student count
        $currentStudentCount = $this->getCurrentStudentCount();
        
        return [
            'id' => $this->id,
            'code' => $this->code,
            'subject_name' => $this->subject_name,
            'term_id' => $this->term_id,
            'enroll_limit' => $this->enroll_limit,
            'current_student_count' => $currentStudentCount,
            'midterm_weight' => $this->midterm_weight,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'term' => new TermResource($this->whenLoaded('term')),
            'students' => UserResource::collection($this->whenLoaded('students')),
            'classroom' => $this->when(isset($this->classroom), $this->classroom),
            // Add student's own enrollment details if available
            'enrollment' => $this->when(
                auth()->check() && $this->students->contains(auth()->id()), 
                function() {
                    $enrollment = $this->students->where('id', auth()->id())->first()->pivot;
                    return [
                        'midterm_grade' => $enrollment->midterm_grade,
                        'final_grade' => $enrollment->final_grade,
                        'total_grade' => $enrollment->total_grade,
                        'status' => $enrollment->status,
                        'notes' => $enrollment->notes,
                    ];
                }
            ),
        ];
    }
}
