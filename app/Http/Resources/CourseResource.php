<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Sử dụng TermResource cho trường term
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'subject_name' => $this->subject_name,
            'term_id' => $this->term_id,
            'enroll_limit' => $this->enroll_limit,
            'midterm_weight' => $this->midterm_weight,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'term' => new TermResource($this->whenLoaded('term')),
        ];
    }
}
