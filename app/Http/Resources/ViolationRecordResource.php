<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ViolationRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request The incoming request.
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Eager load manager relationship if not already loaded
        $this->resource->loadMissing('manager');

        return [
            'id' => $this->id,
            'violation_name' => $this->violation_name,
            'violation_date' => $this->violation_date->toDateString(), // Format date
            'manager_id' => $this->manager_id,
            'manager_name' => $this->whenLoaded('manager', function () {
                return $this->manager->name; // Get name from related manager (User model)
            }),
            'recorded_at' => $this->created_at, // Assuming created_at is the recording time
            'updated_at' => $this->updated_at,
            'is_editable' => $this->isEditable(), // Include editability status
        ];
    }
}
